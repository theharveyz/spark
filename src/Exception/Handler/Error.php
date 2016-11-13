<?php
namespace Spark\Exception\Handler;

use Phalcon\Mvc\Dispatcher\Exception as DispatcherException;
use Phalcon\Dispatcher;
use Spark\Contract\ExceptionInterface;
use Spark\Application as App;

class Error
{
    protected $options = [
        'type' => -1,
        'message' => 'No error message',
        'file' => '',
        'line' => '',
        'exception' => null,
        'errorType' => '',
        'statusCode' => 500,
        'statusMessage' => 'Internal Server Error',
        'isError' => false,
        'logLevel' => 'error',
    ];

    protected $recommendedHttpStatusPhrases = array(
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    );

    public function __construct(array $options)
    {
        $this->options = array_merge($this->options, $options);

        // error type
        $this->errorType = $this->getErrorTypes($this->type);

        if (!$this->isError && $this->exception instanceof DispatcherException) {
            switch ($this->exception->getCode()) {
                case Dispatcher::EXCEPTION_HANDLER_NOT_FOUND:
                case Dispatcher::EXCEPTION_ACTION_NOT_FOUND:
                    $this->statusCode = 404;
                    break;
                case Dispatcher::EXCEPTION_INVALID_PARAMS:
                    $this->statusCode = 401;
                    break;
                default:
                    $this->statusCode = 500;
            }
        }
        if (!$this->isError && $this->exception instanceof ExceptionInterface) {
            $this->statusCode = $this->exception->getStatusCode();
        }

        if (!$this->isError && $this->exception) {
            $this->message = $this->exception->getMessage();
        }

        $this->statusMessage = isset($this->recommendedHttpStatusPhrases[$this->statusCode]) ? $this->recommendedHttpStatusPhrases[$this->statusCode] : 'Unknown Server Error';

        $this->logLevel = $this->getLogLevel($this->errorType);
    }

    protected function getErrorTypes($code)
    {
        switch ($code) {
            case 0:
                return 'UNCAUGHT_EXCEPTION';
            case E_ERROR:
                return 'E_ERROR';
            case E_WARNING:
                return 'E_WARNING';
            case E_PARSE:
                return 'E_PARSE';
            case E_NOTICE:
                return 'E_NOTICE';
            case E_CORE_ERROR:
                return 'E_CORE_ERROR';
            case E_CORE_WARNING:
                return 'E_CORE_WARNING';
            case E_COMPILE_ERROR:
                return 'E_COMPILE_ERROR';
            case E_COMPILE_WARNING:
                return 'E_COMPILE_WARNING';
            case E_USER_ERROR:
                return 'E_USER_ERROR';
            case E_USER_WARNING:
                return 'E_USER_WARNING';
            case E_USER_NOTICE:
                return 'E_USER_NOTICE';
            case E_STRICT:
                return 'E_STRICT';
            case E_RECOVERABLE_ERROR:
                return 'E_RECOVERABLE_ERROR';
            case E_DEPRECATED:
                return 'E_DEPRECATED';
            case E_USER_DEPRECATED:
                return 'E_USER_DEPRECATED';
            default:
                return $code;
        }
    }

    public function getLogLevel($errorType)
    {
        $levelMapping = array(
            'E_ALL' => 'info',
            'E_USER_DEPRECATED' => 'notice',
            'E_DEPRECATED' => 'notice',
            'E_RECOVERABLE_ERROR' => 'notice',
            'E_STRICT' => 'notice',
            'E_USER_NOTICE' => 'notice',
            'E_NOTICE' => 'notice',
            'E_USER_WARNING' => 'warning',
            'E_COMPILE_WARNING' => 'warning',
            'E_CORE_WARNING' => 'warning',
            'E_WARNING' => 'warning',
            'E_USER_ERROR' => 'error',
            'E_COMPILE_ERROR' => 'error',
            'E_CORE_ERROR' => 'error',
            'E_ERROR' => 'error',
            'E_PARSE' => 'emergency',
        );
        return empty($levelMapping[$errorType]) ? 'error' : $levelMapping[$errorType];
    }

    public function __get($name)
    {
        return isset($this->options[$name]) ? $this->options[$name] : null;
    }

    public function __set($name, $value)
    {
        if (!isset($this->options[$name])) {
            return false;
        }
        $this->options[$name] = $value;
        return true;
    }

    public function getTrace()
    {
        $trace = !$this->isError ? "\n#" . $this->exception->__toString() :
            <<<ERROR_MSG
# {$this->errorType}
# {$this->message}
# {$this->file}
# {$this->line}
ERROR_MSG;
        return $trace;
    }

    public function __toString()
    {
        $exception = $this->isError ? false : $this->exception;
        $errorOrException = $exception ? 'EXCEPTION' : 'ERROR';
        /*
        log_format upstream '$remote_addr - $remote_user [$time_local] '
                    '"$request" $status $body_bytes_sent '
                    '"$http_referer" "$http_user_agent" '
                    '$http_x_forwarded_for $host $request_time $upstream_response_time $scheme '
                    '$cookie_evalogin';
        */

        $request =
            empty($_SERVER['REQUEST_METHOD']) || empty($_SERVER['REQUEST_URI'])
            ? '-'
            : $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'];
        return sprintf(
            "%s %s %s [%s] \"%s\" %s %s \"%s\" \"%s\" %s %s %.5f %s %s %s",
            empty($_SERVER['REMOTE_ADDR']) ? '-' : $_SERVER['REMOTE_ADDR'], //remote_addr
            $errorOrException, //
            '-', //remote_user
            time(), //time_local
            $request,
            $this->statusCode,
            '-', //body_bytes_sent
            empty($_SERVER['HTTP_REFERER']) ? '-' : $_SERVER['HTTP_REFERER'],
            empty($_SERVER['HTTP_USER_AGENT']) ? '-' : $_SERVER['HTTP_USER_AGENT'],
            empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? '-' : $_SERVER['HTTP_X_FORWARDED_FOR'],
            empty($_SERVER['HTTP_HOST']) ? '-' : $_SERVER['HTTP_HOST'],
            microtime(true) - App::$appStartTime, //request_time
            '-', //upstream_response_time
            empty($_SERVER['HTTPS']) ? '-' : $_SERVER['HTTPS'], //scheme
            // empty($_COOKIE['']) ? '-' : $_COOKIE[''],
            $this->getTrace()
        );
    }
}
