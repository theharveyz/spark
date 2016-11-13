<?php
namespace Spark\Provider;

use Spark\Contract\ServiceProvider;
use Phalcon\Session\Adapter\Redis as SessionAdapter;

class SessionServiceProvider extends ServiceProvider
{
    protected $name = 'session';

    public function register()
    {
        $app = $this->app;
        $di = $app->di;
        $di->setShared($this->name, function () use($di, $app) {
            $session = new SessionAdapter((array)$di['config']->redis);
            // FIXME: 不要用.操作符相连
            $session->setName($app->getAppName() . '-sid');
            if (!$session->isStarted()) {
                $session->start();
            }
            return $session;
        });
    }
}
