<?php
namespace Spark\Routing;

use Phalcon\Mvc\Router as PhalconRouter;

class Router extends PhalconRouter
{
    const METHODS = [
            'GET',
            'POST',
            'PUT',
            'DELETE',
            'OPTIONS',
            'HEAD',
        ];

    private $routing = [];

    public function __construct()
    {
        // 去除默认规则
        parent::__construct(false);
        // 自动处理结尾斜杠匹配
        $this->removeExtraSlashes(true);
        $this->setUriSource(PhalconRouter::URI_SOURCE_SERVER_REQUEST_URI);
    }

    public function register($routing)
    {
        if (empty($routing)) return $this;

        $this->routing = $routing;

        if (!is_array($routing)) return $this;
        
        foreach ($routing as $uri => $config) {
            if ($uri === 'notFound') {
                $this->setNotFoundRouting($config);
                continue;
            }

            list($conf, $method) = $this->getRoutingConfig($config);
            if (!empty($method)) {
                $this->add($uri, $conf)->via($method);
            } else {
                $this->add($uri, $conf);
            }
        }
        return $this;
    }

    public function getMatchedRoutePattern()
    {
        if (!$matchRoute = $this->getMatchedRoute()) {
            return null;
        }
        return $matchRoute->getPattern();
    }

    public function callMiddlewares()
    {
        $pattern = $this->getMatchedRoutePattern();
        if (!is_null($pattern) && isset($this->routing[$pattern])) {
            $config = $this->routing[$pattern];
            return isset($config['middlewares']) ? $config['middlewares'] : null;
        }
        return null;
    }

    protected function getRoutingConfig($config) 
    {
        $route['controller'] = isset($config['controller']) ? $config['controller'] : 'index';
        $route['action'] = isset($config['action']) ? $config['action'] : 'index';
        if (isset($config['params']) && is_array($config['params'])) {
            $route = array_merge($route, $config['params']);
        }
        $method = [];
        if (isset($config['method'])) {
            $method = array_intersect($config['method'], self::METHODS);
        }
        return [$route, $method];
    }

    protected function setNotFoundRouting($config)
    {
        return $this->notFound($config);
    }
}
