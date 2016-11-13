<?php
namespace Spark\Provider;

use Spark\Contract\ServiceProvider;

class KVStoreServiceProvider extends ServiceProvider
{
    protected $name = 'cache';

    public function register()
    {
        $di = $this->app->di;
        $di->setShared($this->name, function () use ($di) {
            return $di['session']->getRedis();
        });
    }
}
