<?php
namespace Spark\Provider;

use Spark\Contract\ServiceProvider;
use Phalcon\Mvc\Dispatcher;

class DispatcherServiceProvider extends ServiceProvider
{
    protected $name = 'dispatcher';
    
    public function register()
    {
        $di = $this->app->di;
        $di->setShared($this->name, function () use ($di) {
            $dispatcher = new Dispatcher();
            $dispatcher->setEventsManager($di['eventsManager']);
            return $dispatcher;
        });
    }
   
}
