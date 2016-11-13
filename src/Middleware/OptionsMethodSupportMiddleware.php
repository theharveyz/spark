<?php
namespace Spark\Middleware;

use Spark\Contract\MiddlewareInterface;
use Spark\Application;
use Spark\Exception\UnauthorizedException;
use Spark\Auth\JSONWebToken;

class OptionsMethodSupportMiddleware implements MiddlewareInterface
{
    public function handler(Application $app)
    {
        if ($app['request']->getMethod() === 'OPTIONS') {
            $app['response']->send();
            exit;
        }
    }
}
