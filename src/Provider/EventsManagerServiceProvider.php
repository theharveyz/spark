<?php
namespace Spark\Provider;

use Spark\Contract\ServiceProvider;
use Spark\Contract\MiddlewareInterface;
use Phalcon\Events\Manager as EventsManager;
use Spark\Exception\ConfigureException;

class EventsManagerServiceProvider extends ServiceProvider
{
    protected $name = 'eventsManager';
    
    public function register()
    {
        $app = $this->app;
        $app->di->setShared($this->name, function () use ($app) {
            $eventsManager = new EventsManager();
            $eventsManager->attach(
                'dispatch:beforeException',
                function ($event, $dispatcher, $exception) {
                    //For fixing phalcon weird behavior https://github.com/phalcon/cphalcon/issues/2558
                    throw $exception;
                }
            );
            $eventsManager->attach(
                'dispatch:beforeExecuteRoute',
                function ($event, $dispatcher) use ($app) {
                    $router = $app['router'];
                    $middlewares = $router->callMiddlewares();
                    if (empty($middlewares)) return;
                    if (is_string($middlewares)) {
                        $middlewares = [$middlewares];
                    }
                    foreach ($middlewares as $middleware) {
                        $handler = $app->di->get($middleware);
                        if (!$handler) {
                            throw new ConfigureException("{$middleware} is not exists");
                        }
                        if (!$handler instanceof MiddlewareInterface ) {
                            throw new ConfigureException("{$middleware} is not valid middleware");
                        }
                        // 返回false，则不继续执行中间件
                        if (!$handler->handler($app)) {
                            break;
                        }
                    }
                }
            );
            return $eventsManager;
        });
    }
   
}
