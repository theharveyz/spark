<?php
namespace Spark\Provider;

use Spark\Contract\ServiceProvider;
use Phalcon\Mvc\Url as UrlResolver;

class UrlServiceProvider extends ServiceProvider
{
    protected $name = 'url';

    public function register()
    {
        $di = $this->app->di;
        $di->setShared($this->name, function() use ($di) {
            $config = $di->getConfig();

            $url = new UrlResolver();
            $url->setBaseUri($config->application->baseUri);
            return $url;
        });
    }
}
