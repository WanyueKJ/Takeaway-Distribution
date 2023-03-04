<?php

declare(strict_types=1);

namespace Imi\Db\Mysql\Util;

use Imi\Db\Exception\DbException;
use Imi\Db\Query\Interfaces\IQuery;
use Imi\Db\Query\Table;

/**
 * SQL 工具类.
 */
class SqlUtil
{
    private function __construct()
    {
    }

    /**
     * 解析带冒号参数的 SQL，返回解析后的 SQL.
     *
     * @param array $map
     */
    public static function parseSqlWithColonParams(string $sql, ?array &$map): string
    {
        $map = [];

        return preg_replace_callback('/:([a-zA-Z0-9_]+)/', static function (array $match) use (&$map): string {
            $map[] = $match[1];

            return '?';
        }, $sql);
    }

    /**
     * 处理多行 SQL 为一个数组.
     */
    public static function parseMultiSql(string $sql): array
    {
        $result = [];
        $begin = 0;
        $i = 0;
        $sqlLength = \strlen($sql);
        // 关闭单引号
        $closeApostrophe = true;
        for ($i = 0; $i < $sqlLength; ++$i)
        {
            switch ($sql[$i])
            {
                case ';':
                    if ($closeApostrophe)
                    {
                        $sqlString = trim(substr($sql, $begin, $i + 1 - $begin));
                        $begin = $i + 1;
                        if ('' !== $sqlString && ';' !== $sqlString)
                        {
                            $result[] = $sqlString;
                        }
                    }
                    break;
                case '\\':
                    $next = $i + 1;
                    if (isset($sql[$next]) && '\'' === $sql[$next])
                    {
                        // 下个字符是单引号，算转义，跳过
                        $i = $next;
                    }
                    break;
                case '\'':
                    $next = $i + 1;
                    if (isset($sql[$next]) && '\'' === $sql[$next])
                    {
                        // 下个字符是单引号，算转义，跳过
                        $i = $next;
                    }
                    else
                    {
                        $closeApostrophe = !$closeApostrophe;
                    }
                    break;
            }
        }
        $leftSql = substr($sql, $begin, $i + 1 - $begin);
        if ('' !== trim($leftSql))
        {
            throw new DbException(sprintf('Invalid sql: %s', $leftSql));
        }

        return $result;
    }

    /**
     * 生成插入语句.
     */
    public static function buildInsertSql(IQuery $query, string $table, array $dataList): string
    {
        $sql = '';
        $tableObj = new Table();
        $tableObj->setValue($table, $query);
        $tableStr = $tableObj->toString($query);
        foreach ($dataList as $row)
        {
            foreach ($row as &$value)
            {
                if (\is_string($value))
                {
                    $value = '\'' . self::mysqlEscapeString($value) . '\'';
                }
            }
            $sql .= 'insert into ' . $tableStr . ' values(' . implode(',', $row) . ');' . \PHP_EOL;
        }

        return $sql;
    }

    /**
     * 转义 MySQL 字符串.
     *
     * @source https://github.com/abreksa4/mysql-escape-string-polyfill
     */
    public static function mysqlEscapeString(string $value): string
    {
        return strtr($value, [
            "\0"     => '\0',
            "\n"     => '\n',
            "\r"     => '\r',
            "\t"     => '\t',
            \chr(26) => '\Z',
            \chr(8)  => '\b',
            '"'      => '\"',
            '\''     => '\\\'',
            '\\'     => '\\\\',
        ]);
    }
}
