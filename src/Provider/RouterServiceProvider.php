<?php
namespace Spark\Provider;

use Spark\Contract\ServiceProvider;
use Spark\Routing\Router;

class RouterServiceProvider extends ServiceProvider
{
    protected $name = 'router';

    public function register()
    {
        $app = $this->app;
        $app->di->setShared($this->name, function () use($app) {
            // 修改默认的PHPSESSID
            $router = new Router();
            $router->register($app->getRouting());
            return $router;
        });
    }
}
