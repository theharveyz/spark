<?php
namespace Spark\Exception;

use Spark\Exception\LogicException;

class UnauthorizedException extends LogicException
{
    protected $statusCode = 401;
}
