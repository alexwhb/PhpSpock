<?php
/**
 * @author Alex Rudakov <alexandr.rudakov@modera.net>
 */

spl_autoload_register(function($class)
{
    $file = __DIR__.'/../src/'.strtr($class, '\\', '/').'.php';
    if (file_exists($file)) {
        require $file;
        return true;
    }
});

spl_autoload_register(function($class)
{
    $file = __DIR__.'/../lib/mockery/library/'.strtr($class, '\\', '/').'.php';
    if (file_exists($file)) {
        require $file;
        return true;
    }
});
