<?php

declare(strict_types=1);

namespace Imi\Server\Session\Handler;

use Imi\Bean\Annotation\Bean;
use Imi\Util\File as FileUtil;

/**
 * @Bean("SessionFile")
 */
class File extends Base
{
    /**
     * Session文件存储路径.
     */
    protected string $savePath = '';

    /**
     * 执行初始化操作.
     */
    public function __init(): void
    {
        parent::__init();
        FileUtil::createDir($this->savePath);
    }

    /**
     * {@inheritDoc}
     */
    public function destroy(string $sessionId): void
    {
        $fileName = $this->getFileName($sessionId);
        if (is_file($fileName))
        {
            unlink($fileName);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function gc(int $maxLifeTime): void
    {
        $files = new \FilesystemIterator($this->savePath);
        $maxTime = time() - $maxLifeTime;
        foreach ($files as $file)
        {
            $fileName = $file->getPathname();
            $filemtime = filemtime($fileName);
            if (false !== $filemtime && $filemtime <= $maxTime)
            {
                unlink($fileName);
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function read(string $sessionId): string
    {
        $fileName = $this->getFileName($sessionId);
        if (is_file($fileName))
        {
            return file_get_contents($fileName);
        }
        else
        {
            return '';
        }
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $sessionId, string $sessionData, int $maxLifeTime): void
    {
        file_put_contents($this->getFileName($sessionId), $sessionData, \LOCK_EX);
    }

    /**
     * {@inheritDoc}
     */
    public function getFileName(string $sessionId): string
    {
        return FileUtil::path($this->savePath, $sessionId . '.session');
    }
}
