<?php
namespace Spark\Provider\Cli;

use Spark\Contract\ServiceProvider;
use Phalcon\Cli\Dispatcher;

class DispatcherServiceProvider extends ServiceProvider
{
    protected $name = 'dispatcher';
    
    public function register()
    {
        $di = $this->app->di;
        $config = $this->app->getConfig();
        $di->setShared($this->name, function () use ($di, $config) {
            $dispatcher = new Dispatcher();
            $dispatcher->setDefaultTask($config->application->defaultCliTask);
            $dispatcher->setDefaultAction($config->application->defaultCliAction);
            return $dispatcher;
        });
    }
   
}
