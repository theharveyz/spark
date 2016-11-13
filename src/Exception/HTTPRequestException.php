<?php
namespace Spark\Exception;

use Spark\Exception\LogicException;

class HTTPRequestException extends LogicException
{
    protected $statusCode = 400;
}
