<?php
namespace Spark\Provider;

use Spark\Contract\ServiceProvider;

class DbServiceProvider extends ServiceProvider
{
    protected $name = 'db';
    
    public function register()
    {
        $di = $this->app->di;
        $di->setShared($this->name, function () use ($di) {
            $config = $di->getConfig();

            $dbConfig = $config->database->toArray();
            $adapter = $dbConfig['adapter'];
            unset($dbConfig['adapter']);

            $class = 'Phalcon\Db\Adapter\Pdo\\' . $adapter;

            return new $class($dbConfig);
        });
    }
   
}
