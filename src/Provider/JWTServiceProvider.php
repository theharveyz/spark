<?php
namespace Spark\Provider;

use Spark\Contract\ServiceProvider;
use Spark\Auth\JSONWebToken;

class JWTServiceProvider extends ServiceProvider
{
    protected $name = 'jwt';
    
    public function register()
    {
        $di = $this->app->di;
        $di->set($this->name, function () use ($di) {
            $config = $di->getConfig();
            return new JSONWebToken($di['cache'], $config->auth->jwtSignSecretKey);
        });
    }
   
}
