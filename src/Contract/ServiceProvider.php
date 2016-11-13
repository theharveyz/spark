<?php
namespace Spark\Contract;

abstract class ServiceProvider 
{
    protected $name = '';

    protected $app = null;

    public function __construct(\Spark\Application $app)
    {
        $this->app = $app;
    }

    public function getName() 
    {
        return $this->name;
    }

    abstract public function register();

}
