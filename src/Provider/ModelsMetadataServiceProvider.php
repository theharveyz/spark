<?php
namespace Spark\Provider;

use Spark\Contract\ServiceProvider;
use Phalcon\Mvc\Model\Metadata\Memory as MetaDataAdapter;

class ModelsMetadataServiceProvider extends ServiceProvider
{
    protected $name = 'modelsMetadata';

    public function register()
    {
        /**
         * Register the session flash service with the Twitter Bootstrap classes
         */
        $this->app->di->setShared($this->name, function () {
            return new MetaDataAdapter();
        });

    }
}
