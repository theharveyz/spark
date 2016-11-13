<?php
namespace Spark\Exception;

use Phalcon\Exception as PE;
use Spark\Contract\ExceptionInterface;

class StandardException extends PE implements ExceptionInterface
{
    protected $statusCode = 500;

    public function __construct($errorMsg, $code = 10000, $statusCode = null)
    {
        if (is_numeric($statusCode) && $statusCode >= 100 && $statusCode <= 599) {
            $this->statusCode = $statusCode;
        }
        parent::__construct($errorMsg, $code, $statusCode);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }
}
