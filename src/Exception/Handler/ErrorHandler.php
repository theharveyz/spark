<?php
namespace Spark\Exception\Handler;

use Spark\Contract\ErrorHandlerInterface;
use Spark\Application as App;
use Phalcon\Http\ResponseInterface;

class ErrorHandler implements ErrorHandlerInterface
{
    final public static function exceptionHandler($e)
    {
        static::handle(new Error([
                'exception' => $e,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'type' => $e->getCode(),
            ]));
    }

    final public static function errorHandler($errno, $errstr, $errfile, $errline)
    {
        static::handle(new Error([
                'isError' => true,
                'type' => $errno,
                'line' => $errline,
                'file' => $errfile,
                'message' => $errstr,
            ]));
    }

    final public static function shutdownHandler()
    {
        if($options = error_get_last()) {
            $options['isError'] = true;
            static::handle(new Error($options));
        }
    }

    protected static function notFoundError(Error $err)
    {   
        $app = App::getInstance();
        $appRouting = $app->getRouting();
        if (isset($appRouting['notFound'])) {
            $dispatcher = $app['dispatcher'];
            $response = $app['response'];
            $notFoundRouting = $appRouting['notFound'];
            $dispatcher->setControllerName($notFoundRouting['controller']);
            $dispatcher->setActionName($notFoundRouting['action']);
            $dispatcher->dispatch();
            $return = $dispatcher->getReturnedValue();

            // 判断是否已经send，如果send则返回
            if ($response->isSent()) return true;

            // 如果返回内容，则追加
            if (is_string($return) || is_numeric($return)) {
                $response->appendContent($return);
            } 
            return $response->send();
        }
        return false;
    }

    public static function handle(Error $err)
    {

        // 404单独处理
        if ($err->statusCode == 404) {
            if (self::notFoundError($err)) {
                return true;
            }
        }

        static::logger($err);
        $useErrorResponse = true;
        if ($err->isError) {
            switch ($err->type) {
                case E_WARNING:
                case E_NOTICE:
                case E_CORE_WARNING:
                case E_COMPILE_WARNING:
                case E_USER_WARNING:
                case E_USER_NOTICE:
                case E_STRICT:
                case E_DEPRECATED:
                case E_USER_DEPRECATED:
                case E_ALL:
                    $useErrorResponse = false;
                    break;
            }
        }

        if (!$useErrorResponse) {
            return false;
        }

        $app = App::getInstance();
        $response = $app['response'];
        return $response
            ->setStatusCode($err->statusCode, $err->statusMessage)
            ->setJsonContent(self::getErrorResponseContent($err))
            ->send();

    }

    public static function getErrorResponseContent(Error $err)
    {
        $arr = [
            'errorCode' => $err->exception ? $err->exception->getCode() : $err->type,
            'message' => $err->message,
            'name' => $err->exception ? get_class($err->exception) : $err->errorType,
        ];
        $app = App::getInstance();
        if ($app->isDebug()) {
            return array_merge($arr, [
                    'statusCode' => $err->statusCode,
                    'trace' => $err->getTrace(),
                ]);
        }
        return $arr;
    }

    public static function logger($err)
    {
        $config = App::getInstance()['config'];
        $enable = false;
        if(isset($config->error->logger) && isset($config->error->logger->enable) 
            && $config->error->logger->enable) {
            $enable = true;
        }
        // 根据配置来判断是否生成日志
        if($enable) {
            $loggerDir = $config->application->loggerDir;
            $loggerFile = $loggerDir . 'system_error_' . date('Ymd') . '.log';
            logger($loggerFile)->log($err->logLevel, $err);
        }
    }
}
