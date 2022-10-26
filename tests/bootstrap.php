<?php

declare(strict_types=1);

/*
 * This file is part of Contao.
 *
 * (c) Leo Feyer
 *
 * @license LGPL-3.0-or-later
 */

error_reporting(E_ALL);

$include = function ($file) {
    return file_exists($file) ? include $file : false;
};

if (
    false === ($loader = $include(__DIR__.'/../vendor/autoload.php'))
    && false === ($loader = $include(__DIR__.'/../../vendor/autoload.php'))
    && false === ($loader = $include(__DIR__.'/../../../autoload.php'))
) {
    echo 'You must set up the project dependencies, run the following commands:'.PHP_EOL
        .'curl -sS https://getcomposer.org/installer | php'.PHP_EOL
        .'php composer.phar install'.PHP_EOL;

    exit(1);
}

// Autoload the fixture classes
$fixtureLoader = function ($class): void {
    if (class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false)) {
        return;
    }

    if (false !== strpos($class, '\\') && 0 !== strncmp($class, 'Contao\\', 7) && 0 !== strncmp($class, 'Codefog\HasteBundle\\', 19)) {
        return;
    }

    $isContaoClass = false;
    $isBundleClass = false;

    if (0 === strncmp($class, 'Contao\\', 7)) {
        $class = substr($class, 7);
        $isContaoClass = true;
    }

    if (0 === strncmp($class, 'Codefog\HasteBundle\\', 19)) {
        $class = substr($class, 19);
        $isBundleClass = true;
    }

    $file = strtr($class, '\\', '/');

    if (file_exists(__DIR__.'/Fixtures/'.$file.'.php')) {
        include_once __DIR__.'/Fixtures/'.$file.'.php';

        if ($isContaoClass) {
            class_alias('Codefog\HasteBundle\Tests\Fixtures\\'.$class, 'Contao\\'.$class);
        } elseif ($isBundleClass) {
            class_alias('Codefog\HasteBundle\Tests\Fixtures\\'.$class, 'Codefog\HasteBundle\\'.$class);
        }
    }

    $namespaced = 'Contao\\'.$class;

    if (!class_exists($namespaced) && !interface_exists($namespaced) && !trait_exists($namespaced)) {
        return;
    }

    if (!class_exists($class, false) && !interface_exists($class, false) && !trait_exists($class, false)) {
        class_alias($namespaced, $class);
    }
};

spl_autoload_register($fixtureLoader, true, true);

return $loader;
