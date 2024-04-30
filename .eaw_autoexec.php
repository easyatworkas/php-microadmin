<?php
require (__DIR__ . '/vendor/autoload.php');
require ('autoloader.php');
require ('shared.php');

// Set root of project
define('SCRIPT_ROOT', realpath(__DIR__));

// Reset Global Variable for File-logging
$GLOBALS['log_file'] = null;

// Create Aliases for all Models in Ext/Models/
/** @var string[] $aliases */
$aliases = [];

foreach (glob('Ext/Models/*.php') as $file) {
    $longname = substr($file, 0, strrpos($file, '.'));
    $shortname = substr($longname, strrpos($longname, '/') + 1);

    $aliases[$shortname] = str_replace('/', '\\', $longname);
}

/**
 * getClass is needed because 'get_class' does not return the full class name, only the Alias
 *
 * @param $object
 * @return string
 */
function getClass($object)
{
    global $aliases;

    $class = get_class($object);

    return $aliases[$class] ?? $class;
}

spl_autoload_register(function ($class) use ($aliases) {
    if ($alias = $aliases[$class] ?? null) {
        eval('class ' . $class . ' extends ' . $alias . ' {}');
    }
});
