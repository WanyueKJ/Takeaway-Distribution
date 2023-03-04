<?php

declare(strict_types=1);

namespace Imi\Macro;

use Composer\Autoload\ClassLoader;
use Swoole\Coroutine;
use function Yurun\Macro\includeFile;
use Yurun\Macro\MacroParser;

class AutoLoader extends ClassLoader
{
    protected ClassLoader $composerClassLoader;

    protected string $macroPath;

    protected bool $hasSwoole = false;

    public function __construct(ClassLoader $composerClassLoader)
    {
        $this->composerClassLoader = $composerClassLoader;
        $this->hasSwoole = \extension_loaded('swoole');
    }

    /**
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->composerClassLoader->$name(...$arguments);
    }

    /**
     * Loads the given class or interface.
     *
     * @param string $class The name of the class
     *
     * @return true|null True if loaded, null otherwise
     */
    public function loadClass($class)
    {
        $fileName = $this->composerClassLoader->findFile($class);
        if (false === $fileName)
        {
            return null;
        }
        if ($this->hasSwoole && Coroutine::getCid() >= 0)
        {
            $flags = \Swoole\Runtime::getHookFlags();
            \Swoole\Runtime::enableCoroutine($flags & ~(\SWOOLE_HOOK_FILE | \SWOOLE_HOOK_STDIO));
            $this->includeFile($fileName);
            \Swoole\Runtime::enableCoroutine($flags);
        }
        else
        {
            $this->includeFile($fileName);
        }

        return true;
    }

    private function includeFile(string $fileName): void
    {
        $macroFileName = $fileName . '.macro';
        if (file_exists($macroFileName))
        {
            MacroParser::includeFile($macroFileName, $macroFileName . '.php', false);
        }
        elseif (preg_match('/^\s*#\s*macro$/mUS', file_get_contents($fileName) ?: ''))
        {
            MacroParser::includeFile($fileName, $fileName . '.macro.php', false);
        }
        else
        {
            includeFile($fileName);
        }
    }

    public function getComposerClassLoader(): ClassLoader
    {
        return $this->composerClassLoader;
    }
}
