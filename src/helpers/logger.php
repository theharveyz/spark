<?php
if(!function_exists('logger')) {
    function logger($file) {
        $logger = new Phalcon\Logger\Adapter\File($file);
        return $logger;
    }
}
