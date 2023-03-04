<?php

declare(strict_types=1);

namespace Imi\Server\Session;

use Imi\Facade\Annotation\Facade;
use Imi\Facade\BaseFacade;

/**
 * @Facade(class="SessionManager", request=true)
 *
 * @method static void start(?string $sessionId = NULL)
 * @method static void close()
 * @method static void destroy()
 * @method static void save()
 * @method static void commit()
 * @method static boolean isStart()
 * @method static string getName()
 * @method static string getId()
 * @method static ISessionHandler getHandler()
 * @method static void tryGC()
 * @method static void gc()
 * @method static mixed get($name = NULL, $default = NULL)
 * @method static void set($name, $value)
 * @method static void delete($name)
 * @method static mixed once($name, $default = NULL)
 * @method static void clear()
 * @method static SessionConfig getConfig()
 * @method static string parseName($name)
 * @method static boolean isChanged()
 * @method static boolean isNewSession()
 */
class Session extends BaseFacade
{
}
