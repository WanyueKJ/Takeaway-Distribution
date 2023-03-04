<?php

declare(strict_types=1);

namespace Imi\Cli\Tools\Imi;

use Imi\Bean\Scanner;
use Imi\Cli\Annotation\Command;
use Imi\Cli\Annotation\CommandAction;
use Imi\Cli\Annotation\Option;
use Imi\Cli\ArgType;
use Imi\Cli\Contract\BaseCommand;
use Imi\Core\Runtime\Runtime;
use Imi\Log\Log;
use Imi\Pool\Annotation\PoolClean;
use Imi\Util\File;
use Imi\Util\Imi as ImiUtil;
use Imi\Util\Text;

/**
 * @Command("imi")
 */
class Imi extends BaseCommand
{
    /**
     * 构建框架预加载缓存.
     *
     * @CommandAction(name="buildImiRuntime", description="构建框架预加载缓存")
     * @Option(name="file", type=ArgType::STRING, default=null, comments="可以指定生成到目标文件")
     * @Option(name="runtimeMode", type=ArgType::STRING, default=null, comments="指定运行时模式")
     */
    public function buildImiRuntime(?string $file, ?string $runtimeMode = null): void
    {
        if (null === $file)
        {
            $file = \Imi\Util\Imi::getModeRuntimePath($runtimeMode, 'imi-runtime');
        }
        ImiUtil::buildRuntime($file);
        $this->output->writeln('<info>Build imi runtime complete</info>');
    }

    /**
     * 清除框架预加载缓存.
     *
     * @CommandAction(name="clearImiRuntime", description="清除框架预加载缓存")
     * @Option(name="runtimeMode", type=ArgType::STRING, default=null, comments="指定运行时模式")
     */
    public function clearImiRuntime(?string $runtimeMode = null): void
    {
        $file = ImiUtil::getModeRuntimePath($runtimeMode, 'imi-runtime');
        if (File::deleteDir($file))
        {
            $this->output->writeln('<info>Clear imi runtime complete</info>');
        }
        else
        {
            $this->output->writeln('<error>Imi runtime does not exists</error>');
        }
    }

    /**
     * 构建项目预加载缓存.
     *
     * @PoolClean
     *
     * @CommandAction(name="buildRuntime", description="构建项目预加载缓存")
     *
     * @Option(name="changedFilesFile", type=ArgType::STRING, default=null, comments="保存改变的文件列表的文件，一行一个")
     * @Option(name="confirm", type=ArgType::BOOL, default=false, comments="是否等待输入y后再构建")
     * @Option(name="runtimeMode", type=ArgType::STRING, default=null, comments="指定运行时模式")
     */
    public function buildRuntime(?string $changedFilesFile, bool $confirm, ?string $runtimeMode = null): void
    {
        if (null !== $runtimeMode)
        {
            Runtime::setRuntimeModeHandler(Text::toPascalName($runtimeMode) . 'RuntimeModeHandler')->init();
        }
        if ($confirm)
        {
            $input = fread(\STDIN, 1);
            if ('y' !== $input)
            {
                exit(255);
            }
        }

        $runtimeFileName = ImiUtil::getModeRuntimePath($runtimeMode, 'runtime');
        if (!Text::isEmpty($changedFilesFile) && \Imi\Util\Imi::loadRuntimeInfo($runtimeFileName))
        {
            $files = explode("\n", file_get_contents($changedFilesFile));
            ImiUtil::incrUpdateRuntime($files);
        }
        elseif ($confirm)
        {
            Scanner::scanVendor();
            Scanner::scanApp();
        }
        ImiUtil::buildRuntime($runtimeFileName);
        Log::info('Build app runtime complete');
    }

    /**
     * 清除项目预加载缓存.
     *
     * @CommandAction(name="clearRuntime", description="清除项目预加载缓存")
     * @Option(name="runtimeMode", type=ArgType::STRING, default=null, comments="指定运行时模式")
     */
    public function clearRuntime(?string $runtimeMode = null): void
    {
        $file = \Imi\Util\Imi::getModeRuntimePath($runtimeMode, 'runtime');
        if (File::deleteDir($file))
        {
            $this->output->writeln('<info>Clear app runtime complete</info>');
        }
        else
        {
            $this->output->writeln('<error>App runtime does not exists</error>');
        }
    }
}
