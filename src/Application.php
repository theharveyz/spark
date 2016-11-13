<?php
/**
 * 框架服务定位器，简化启动逻辑
 * 支持WEB/CLI形式
 */
namespace Spark;

use Phalcon\Di\FactoryDefault as DI;
use Spark\Exception\RuntimeException;
use Spark\Exception\ConfigureException;
use Spark\Exception\Handler\ErrorHandler;
use Phalcon\Di\FactoryDefault\Cli as CliDI;
use ArrayAccess;

final class Application implements ArrayAccess
{

    public $di = NULL;
    public static $appStartTime = 0;
    private static $instance = NULL;
    private $appRoot = NULL;
    private $appName = 'Spark';
    private $routings = [];
    private $environments = ['testing', 'development', 'production'];
    private $appEnv = NULL;
    private $appMod = NULL;
    private $appMods = [
        'cli' => 'cli',
        'web' => 'web',
    ];

    public function __construct($appRoot = null, $appMod = 'web', $appName = 'spark')
    {
        // 启动时间
        self::$appStartTime = microtime(true);
        $this->appRoot = $appRoot;
        $this->appName = isset($_SERVER['APP_NAME']) ? $_SERVER['APP_NAME'] : $appName;
        $this->appEnv = isset($_SERVER['APP_ENV']) ? $_SERVER['APP_ENV'] : 'development';
        $this->appMod = $appMod && isset($this->appMods[$appMod]) ? $this->appMods[$appMod] : 'web';
        // 初始化实例
        self::$instance = $this;
        $this->registerConfig();
        $this->registerAutoLoader();
        $this->loadHelpers(__DIR__ . '/helpers');
        $this->setErrorHandler();
        $this->registerBaseServices();
    }

    protected function registerConfig()
    {
        $env = $this->getEnv();
        $configPath = $this->getConfigPath();
        $prefix = 'config.';
        $this->getDi()->setShared('config', function() use ($env, $configPath, $prefix) {
            $defaultConfig = include $configPath . DS . $prefix . 'default.php';
            $config = new \Phalcon\Config($defaultConfig);
            $envConfigFile = $configPath . DS . $prefix . $env . '.php';
            if (file_exists($envConfigFile)) {
                $config->merge(new \Phalcon\Config(include $envConfigFile));
            }

            $localConfigFile = $configPath . DS . $prefix . $env . '.local.php';
            if (file_exists($localConfigFile)) {
                $config->merge(new \Phalcon\Config(include $localConfigFile));
            }
            return $config;
        });

    }

    protected function getConfigPath()
    {
        return APP_PATH . "/config";
    }

    public function getConfig()
    {
        return $this->getDi()->getConfig();
    }

    public function loadHelpers($helperDir)
    {
        if (!is_dir($helperDir)) {
            throw new RuntimeException("The path $helperDir isnot exists", 1);
        }
        foreach (glob($helperDir . DS . '*.php') as $filename) {
            if ($filename) {
                include $filename;
            }
        }
    }

    public function registerRouting($routingDir)
    {
        if (is_dir($routingDir)) {
            foreach(glob($routingDir . DS . '*.php') as $filename) {
                $routingConfig = include $filename;
                if (empty($routingConfig) || !is_array($routingConfig))
                    continue;
                $this->routings = array_merge($this->routings, $routingConfig);
            }
        } else {
            throw new RuntimeException('routingDir is not exists');
        }
    }

    public function getRouting()
    {
        return $this->routings;
    }

    protected function registerBaseServices() 
    {
        $baseProviders = $this->appMod === 'cli' ? $this->getCliBaseProviders()
            : $this->getBaseProviders();
        foreach ($baseProviders as $key => $value) {
            $provider = new $value($this);
            $provider->register();
        }
    }

    protected function getCliBaseProviders()
    {
        return [
            'dispatcher' => \Spark\Provider\Cli\DispatcherServiceProvider::class,
            'command' => \Spark\Provider\Cli\CommandParseServiceProvider::class,
            'cache' => \Spark\Provider\KVStoreServiceProvider::class,
            'db' => \Spark\Provider\DbServiceProvider::class,
        ];   
    }

    protected function getBaseProviders()
    {
        return [
            'dispatcher' => \Spark\Provider\DispatcherServiceProvider::class,
            'eventsManager' => \Spark\Provider\EventsManagerServiceProvider::class,
            'view' => \Spark\Provider\ViewServiceProvider::class,
            'url' => \Spark\Provider\UrlServiceProvider::class,
            'modelsMetadata' => \Spark\Provider\ModelsMetadataServiceProvider::class,
            // 'flash' => \Spark\Provider\FlashServiceProvider::class,
            'session' => \Spark\Provider\SessionServiceProvider::class,
            'cache' => \Spark\Provider\KVStoreServiceProvider::class,
            'db' => \Spark\Provider\DbServiceProvider::class,
            'router' => \Spark\Provider\RouterServiceProvider::class,
            'jwt' => \Spark\Provider\JWTServiceProvider::class,
        ];
    }

    protected function registerAutoLoader() 
    {
        $di = $this->getDi();
        $self = $this;
        $di->setShared('loader', function () use ($di, $self) {
            $loader = new \Phalcon\Loader();
            $loader->registerDirs(
                $self->getAutoLoaderDirs()
            )->register();
            return $loader;
        });
    }

    protected function getAutoLoaderDirs()
    {
        /**
         * We're a registering a set of directories taken from the configuration file
         */
        $config = $this->getConfig();
        $dirs = [
                $config->application->controllersDir,
                $config->application->modelsDir,
            ];

        // cli 模式注册tasks 文件夹
        if ($this->appMod === 'cli') {
            $dirs[] = $config->application->tasksDir;
        }
        return $dirs;
    }

    public function boot() 
    {
        $this->registerRouting($this['config']->application->routingDir);

        $this->getDi()->getLoader();
        $application = new \Phalcon\Mvc\Application($this->di);
        $application->handle()->send();
    }

    public function bootCLI($arguments = [])
    {
        $this->getDi()->getLoader();
        $application = new \Phalcon\Cli\Console($this->di);

        // 方便task中链式调用其他task
        $this['console'] = $application;
        // 做一次合并，支持解析cli
        $arguments = array_merge(
                $this->getCliArguments(), 
                is_array($arguments) ? $arguments : []
            );
        // var_dump($arguments);
        $application->handle($arguments);
    }

    public function getCliArguments()
    {
        $command = $this->getDi()->getCommand();
        $command->option('t')
                ->aka('task')
                ->describedAs('The task name.');

        $command->option('a')
                ->aka('action')
                ->describedAs('The action name.');

        $command->option('p')
                ->aka('params')
                ->describedAs('The params.');
        return [
            'task' => $command['task'],
            'action' => $command['action'],
            'params' => $command['params'],
        ];
    }

    public function getDi() 
    {
        if (!is_null($this->di)) {
            return $this->di;
        }
        $this->di = $this->appMod === 'cli' ? new CliDI() : new DI();
        return $this->di;
    } 

    public function offsetGet($offset) 
    {
        return $this->getDi()[$offset];
    }

    public function offsetSet($offset, $define)
    {
        return NULL;
    }

    public function offsetUnset($offset)
    {
        return NULL;
    }

    public function offsetExists($offset) 
    {
        return isset($this->getDi()[$offset]);
    }

    public function setErrorHandler()
    {
        if ($this->appMod == 'web') {
            $errorHandlerClass = ErrorHandler::class;
            set_exception_handler([$errorHandlerClass, 'exceptionHandler']);
            set_error_handler([$errorHandlerClass, 'errorHandler']);
            register_shutdown_function([$errorHandlerClass, 'shutdownHandler']);
        } else {

        }

        return $this;
    }

    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function isDebug()
    {
        $env = $this->getEnv();
        if (!in_array($env, $this->environments)) {
            throw new ConfigureException('The environments configure is incorrect!');
        }
        // debug用来判断是否打印Error信息，生产环境下error信息不应该打印出来
        return $env !== 'production';
    }

    public function getEnv()
    {
        return $this->appEnv;
    }
    public function getAppName()
    {
        return $this->appName;
    }
}
