<?php

declare(strict_types=1);

namespace Imi\Db\Mysql\Drivers\PdoMysql;

use Imi\App;
use Imi\Bean\Annotation\Bean;
use Imi\Config;
use Imi\Db\Exception\DbException;
use Imi\Db\Mysql\Contract\IMysqlStatement;
use Imi\Db\Mysql\Drivers\MysqlBase;
use Imi\Db\Mysql\Util\SqlUtil;
use Imi\Db\Statement\StatementManager;
use Imi\Db\Transaction\Transaction;
use PDO;

/**
 * PDO MySQL驱动.
 *
 * @Bean("PdoMysqlDriver")
 */
class Driver extends MysqlBase
{
    /**
     * 连接对象
     */
    protected ?PDO $instance = null;

    /**
     * 最后执行过的SQL语句.
     */
    protected string $lastSql = '';

    /**
     * Statement.
     *
     * @var \PDOStatement|bool|null
     */
    protected $lastStmt = null;

    /**
     * 是否缓存 Statement.
     */
    protected bool $isCacheStatement = false;

    /**
     * 事务管理.
     */
    protected Transaction $transaction;

    /**
     * 参数格式：
     * [
     * 'host'       => 'MySQL IP地址',
     * 'username'   => '数据用户',
     * 'password'   => '数据库密码',
     * 'database'   => '数据库名',
     * 'port'       => 'MySQL端口 默认3306 可选参数',
     * 'charset'    => '字符集',
     * 'options'    => [], // PDO连接选项
     * ].
     */
    public function __construct(array $option = [])
    {
        $option['username'] ??= 'root';
        $option['password'] ??= '';
        $option['options'] ??= [];
        $options = &$option['options'];
        $options[\PDO::ATTR_STRINGIFY_FETCHES] ??= false;
        $options[\PDO::ATTR_EMULATE_PREPARES] ??= false;
        $options[\PDO::ATTR_ERRMODE] ??= \PDO::ERRMODE_EXCEPTION;
        parent::__construct($option);
        $this->isCacheStatement = Config::get('@app.db.statement.cache', true);
        $this->transaction = new Transaction();
    }

    /**
     * 构建DNS字符串.
     */
    protected function buildDSN(): string
    {
        $option = $this->option;
        if (isset($option['dsn']))
        {
            return $option['dsn'];
        }

        return 'mysql:'
                 . 'host=' . ($option['host'] ?? '127.0.0.1')
                 . ';port=' . ($option['port'] ?? '3306')
                 . ';dbname=' . ($option['database'] ?? '')
                 . ';unix_socket=' . ($option['unix_socket'] ?? '')
                 . ';charset=' . ($option['charset'] ?? 'utf8')
                 ;
    }

    /**
     * {@inheritDoc}
     */
    public function isConnected(): bool
    {
        return (bool) $this->instance;
    }

    /**
     * {@inheritDoc}
     */
    public function ping(): bool
    {
        $instance = $this->instance;
        if (!$instance)
        {
            return false;
        }
        try
        {
            if ($instance->getAttribute(\PDO::ATTR_SERVER_INFO))
            {
                return true;
            }
            if ($this->checkCodeIsOffline($instance->errorInfo()[1] ?? 0))
            {
                $this->close();
            }

            return false;
        }
        catch (\PDOException $e)
        {
            if ($this->checkCodeIsOffline($e->errorInfo[1]))
            {
                $this->close();
            }
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function open(): bool
    {
        $option = $this->option;
        $this->instance = new \PDO($this->buildDSN(), $option['username'], $option['password'], $option['options']);
        $this->execInitSqls();

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function close(): void
    {
        StatementManager::clear($this);
        if (null !== $this->lastStmt)
        {
            $this->lastStmt = null;
        }
        $this->instance = null;
        $this->transaction->init();
    }

    /**
     * {@inheritDoc}
     */
    public function getInstance(): ?PDO
    {
        return $this->instance;
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction(): bool
    {
        try
        {
            if (!$this->inTransaction() && !$this->instance->beginTransaction())
            {
                if ($this->checkCodeIsOffline($this->instance->errorInfo()[1] ?? 0))
                {
                    $this->close();
                }

                return false;
            }
            $this->exec('SAVEPOINT P' . $this->getTransactionLevels());
            $this->transaction->beginTransaction();
        }
        catch (\PDOException $e)
        {
            if ($this->checkCodeIsOffline($e->errorInfo[1]))
            {
                $this->close();
            }
            throw $e;
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function commit(): bool
    {
        try
        {
            if (!$this->instance->commit())
            {
                if ($this->checkCodeIsOffline($this->instance->errorInfo()[1] ?? 0))
                {
                    $this->close();
                }

                return false;
            }
        }
        catch (\PDOException $e)
        {
            if ($this->checkCodeIsOffline($e->errorInfo[1]))
            {
                $this->close();
            }
            throw $e;
        }

        return $this->transaction->commit();
    }

    /**
     * {@inheritDoc}
     */
    public function rollBack(?int $levels = null): bool
    {
        if (null === $levels)
        {
            try
            {
                $result = $this->instance->rollback();
            }
            catch (\PDOException $e)
            {
                if ($this->checkCodeIsOffline($e->errorInfo[1]))
                {
                    $this->close();
                }
                throw $e;
            }
        }
        else
        {
            $this->exec('ROLLBACK TO P' . ($this->getTransactionLevels()));
            $result = true;
        }
        if ($result)
        {
            $this->transaction->rollBack($levels);
        }
        elseif ($this->checkCodeIsOffline($this->instance->errorInfo()[1] ?? 0))
        {
            $this->close();
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getTransactionLevels(): int
    {
        return $this->transaction->getTransactionLevels();
    }

    /**
     * {@inheritDoc}
     */
    public function inTransaction(): bool
    {
        return $this->instance->inTransaction();
    }

    /**
     * {@inheritDoc}
     */
    public function errorCode()
    {
        if ($this->lastStmt)
        {
            return $this->lastStmt->errorCode();
        }
        else
        {
            return $this->instance->errorCode();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function errorInfo(): string
    {
        if ($this->lastStmt)
        {
            $errorInfo = $this->lastStmt->errorInfo();
        }
        else
        {
            $errorInfo = $this->instance->errorInfo();
        }
        if (null === $errorInfo[1] && null === $errorInfo[2])
        {
            return '';
        }

        return $errorInfo[1] . ':' . $errorInfo[2];
    }

    /**
     * {@inheritDoc}
     */
    public function lastSql(): string
    {
        return $this->lastSql;
    }

    /**
     * {@inheritDoc}
     */
    public function exec(string $sql): int
    {
        $this->lastSql = $sql;
        $this->lastStmt = null;

        try
        {
            $result = $this->instance->exec($sql);
            if (false === $result)
            {
                $errorCode = $this->errorCode();
                $errorInfo = $this->errorInfo();
                if ($this->checkCodeIsOffline($this->instance->errorInfo()[1] ?? 0))
                {
                    $this->close();
                }
                throw new DbException('SQL exec error [' . $errorCode . '] ' . $errorInfo . \PHP_EOL . 'sql: ' . $sql . \PHP_EOL);
            }
        }
        catch (\PDOException $e)
        {
            if ($this->checkCodeIsOffline($e->errorInfo[1]))
            {
                $this->close();
            }
            throw $e;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function batchExec(string $sql): array
    {
        $result = [];
        foreach (SqlUtil::parseMultiSql($sql) as $itemSql)
        {
            $queryResult = $this->query($itemSql);
            $result[] = $queryResult->fetchAll();
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getAttribute($attribute)
    {
        return $this->instance->getAttribute($attribute);
    }

    /**
     * {@inheritDoc}
     */
    public function setAttribute($attribute, $value): bool
    {
        return $this->instance->setAttribute($attribute, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function lastInsertId(?string $name = null): string
    {
        return $this->instance->lastInsertId($name);
    }

    /**
     * {@inheritDoc}
     */
    public function rowCount(): int
    {
        return null === $this->lastStmt ? 0 : $this->lastStmt->rowCount();
    }

    /**
     * {@inheritDoc}
     */
    public function prepare(string $sql, array $driverOptions = []): IMysqlStatement
    {
        if ($this->isCacheStatement && $stmtCache = StatementManager::get($this, $sql))
        {
            $stmt = $stmtCache['statement'];
        }
        else
        {
            try
            {
                $this->lastSql = $sql;
                $lastStmt = $this->lastStmt = $this->instance->prepare($sql, $driverOptions);
                // @phpstan-ignore-next-line
                if (false === $lastStmt)
                {
                    $errorCode = $this->errorCode();
                    $errorInfo = $this->errorInfo();
                    if ($this->checkCodeIsOffline($this->instance->errorInfo()[1] ?? 0))
                    {
                        $this->close();
                    }
                    throw new DbException('SQL prepare error [' . $errorCode . '] ' . $errorInfo . \PHP_EOL . 'sql: ' . $sql . \PHP_EOL);
                }
                $stmt = App::getBean(Statement::class, $this, $lastStmt);
                if ($this->isCacheStatement && !isset($stmtCache))
                {
                    StatementManager::setNX($stmt, true);
                }
            }
            catch (\PDOException $e)
            {
                if ($this->checkCodeIsOffline($e->errorInfo[1]))
                {
                    $this->close();
                }
                throw $e;
            }
        }

        return $stmt;
    }

    /**
     * {@inheritDoc}
     */
    public function query(string $sql): IMysqlStatement
    {
        try
        {
            $this->lastSql = $sql;
            $this->lastStmt = $lastStmt = $this->instance->query($sql);
            if (false === $lastStmt)
            {
                $errorCode = $this->errorCode();
                $errorInfo = $this->errorInfo();
                if ($this->checkCodeIsOffline($this->instance->errorInfo()[1] ?? 0))
                {
                    $this->close();
                }
                throw new DbException('SQL query error [' . $errorCode . '] ' . $errorInfo . \PHP_EOL . 'sql: ' . $sql . \PHP_EOL);
            }
        }
        catch (\PDOException $e)
        {
            if ($this->checkCodeIsOffline($e->errorInfo[1]))
            {
                $this->close();
            }
            throw $e;
        }

        return App::getBean(Statement::class, $this, $lastStmt);
    }

    /**
     * {@inheritDoc}
     */
    public function getTransaction(): Transaction
    {
        return $this->transaction;
    }
}
