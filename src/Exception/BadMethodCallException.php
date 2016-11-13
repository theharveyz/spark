<?php
namespace Spark\Exception;

use Spark\Exception\LogicException;

class BadMethodCallException extends LogicException
{
    protected $statusCode = 405;
}
