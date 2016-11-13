<?php
namespace Spark\Provider;

use Spark\Contract\ServiceProvider;
use Phalcon\Mvc\View;
use Phalcon\Mvc\View\Engine\Volt as VoltEngine;

class ViewServiceProvider extends ServiceProvider
{
    protected $name = 'view';

    public function register()
    {
        $di = $this->app->di;
        $di->setShared($this->name, function () use ($di) {
            $config = $di->getConfig();
            $view = new View();
            $view->setViewsDir($config->application->viewsDir);

            $view->registerEngines([
                '.volt' => function ($view, $di) {
                    $config = $di->getConfig();

                    $volt = new VoltEngine($view, $di);

                    $volt->setOptions([
                        'compiledPath' => $config->application->cacheDir,
                        'compiledSeparator' => '_'
                    ]);

                    return $volt;
                },
                '.phtml' => 'Phalcon\Mvc\View\Engine\Php'
            ]);

            return $view;
        });
    }
}
