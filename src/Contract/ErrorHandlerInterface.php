<?php
namespace Spark\Contract;

interface ErrorHandlerInterface 
{
    public static function exceptionHandler($e);

    public static function errorHandler($errno, $errstr, $errfile, $errline);

    public static function shutdownHandler();

}
