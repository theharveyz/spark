<?php
if(!function_exists('p')) {
    function p($r) {
        if (function_exists('xdebug_var_dump')) {
            echo '<pre>';
            xdebug_var_dump($r);
            echo '</pre>';
            //(new \Phalcon\Debug\Dump())->dump($r, true);
        } else {
            echo '<pre>';
            var_dump($r);
            echo '</pre>';
        }
    }    
}


/**
 * 打印指定的变量并结束程序运行
 *
 * @param $r
 */
if (!function_exists('dd')) {
    function dd($r) {
        p($r);
        exit();
    }    
}

