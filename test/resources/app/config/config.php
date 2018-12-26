<?php
declare(strict_types=1);

use Zend\Stdlib\ArrayUtils;
use Zend\Stdlib\Glob;

$cachedConfigFile = dirname(__DIR__) . '/cache/app_config.php';

$config = [];

if (is_file($cachedConfigFile)) {
    return require $cachedConfigFile;
}
foreach (Glob::glob(__DIR__ . '/autoload/{{,*.}global,{,*.}local}.php', Glob::GLOB_BRACE) as $file) {
    $config = ArrayUtils::merge($config, include $file);
}
if ((isset($config['app']['config_cache_enabled']) && $config['app']['config_cache_enabled'] === true)
    ||
    (isset($config['app']['environment']) && $config['app']['environment'] === 'prod')
) {
    file_put_contents(
        $cachedConfigFile,
        '<?php return ' . var_export($config, true) . ';',
        LOCK_EX
    );
}

return $config;
