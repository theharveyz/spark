<?php
namespace Spark\Exception;

use Spark\Exception\LogicException;

class InvalidArgumentsException extends LogicException
{
    // bad request
    protected $statusCode = 400;
}
