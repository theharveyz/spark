<?php
namespace Spark\Exception;

use Spark\Exception\LogicException;

class VerifyFailedException extends LogicException
{
    protected $statusCode = 403;
}
