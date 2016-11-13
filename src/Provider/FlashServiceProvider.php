<?php
namespace Spark\Provider;

use Spark\Contract\ServiceProvider;
use Phalcon\Flash\Direct as Flash;

class FlashServiceProvider extends ServiceProvider
{
    protected $name = 'flash';

    public function register()
    {
        /**
         * Register the session flash service with the Twitter Bootstrap classes
         */
        $this->app->di->set($this->name, function () {
            return new Flash([
                'error'   => 'alert alert-danger',
                'success' => 'alert alert-success',
                'notice'  => 'alert alert-info',
                'warning' => 'alert alert-warning'
            ]);
        });
    }
}
