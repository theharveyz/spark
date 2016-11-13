<?php
namespace Spark\Middleware;

use Spark\Contract\MiddlewareInterface;
use Spark\Application;
use Spark\Exception\UnauthorizedException;
use Spark\Auth\JSONWebToken;

class AuthenticateMiddleware implements MiddlewareInterface
{
    public function handler(Application $app)
    {
        if ($app['request']->getMethod() === 'OPTIONS') {
            $app['response']->send();
            exit;
        }
        // 获取x-token
        $token = null;
        $faker = $app->getConfig()->auth->faker;
        if ($faker->enable) {
            $app['session']->set('auth', [
                    'uid' => $faker->uid,
                    'username' => $faker->username,
                    'expireAt' => 0,
                    'type' => 'faker',
                ]);
            return true;
        }
        $token = $app['request']->getHeader('X-TOKEN') ?: $app['request']->get('token');
        $tokenSaveItems = null;
        if ($token) {
            $tokenSaveItems = $app['jwt']->find($token);
        }

        if (isset($tokenSaveItems['uid']) && $tokenSaveItems['uid']) {
            $tokenSaveItems['type'] = 'jwt';
            $tokenSaveItems['token'] = $token;
            $app['session']->set('auth', $tokenSaveItems);
            return true;
        }

        // SESSION存在也通过
        // 并且限定当为faker时不通过
        if ($session = $app['session']->get('auth')) {
            if ($session['uid'] && $session['type'] != 'faker') {
                return true;
            }
        }
        throw new UnauthorizedException('Unauthorized!');
    }

}
