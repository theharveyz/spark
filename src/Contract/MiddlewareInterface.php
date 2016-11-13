<?php
namespace Spark\Contract;
use Spark\Application;

interface MiddlewareInterface 
{
    public function handler(Application $app);
}
