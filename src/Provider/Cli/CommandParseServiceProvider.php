<?php
namespace Spark\Provider\Cli;

use Spark\Contract\ServiceProvider;
use Commando\Command;

class CommandParseServiceProvider extends ServiceProvider
{
    protected $name = 'command';
    
    public function register()
    {
        $di = $this->app->di;
        $di->set($this->name, function () {
            return new Command();
        });
    }
   
}
