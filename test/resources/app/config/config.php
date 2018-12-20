<?php
declare(strict_types=1);

use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\Glob;

$cachedConfigFile = dirname(__DIR__) . '/cache/app_config.php';

$config = [];

if (is_file($cachedConfigFile)) {
    // Try to load the cached config
    $config = json_decode(file_get_contents($cachedConfigFile), true);
} else {
    // Load configuration from autoload path
    foreach (Glob::glob(__DIR__ . '/autoload/{{,*.}global,{,*.}local}.php', Glob::GLOB_BRACE) as $file) {
        $config = ArrayUtils::merge($config, include $file);
    }
    // Cache config if enabled
    if (isset($config['app']['config_cache_enabled']) && $config['app']['config_cache_enabled'] === true) {
        file_put_contents($cachedConfigFile, json_encode($config));
    }
}

// Return an ArrayObject so we can inject the config as a service in Aura.Di
// and still use array checks like ``is_array``.
return $config;
