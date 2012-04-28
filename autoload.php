<?php

if (false === class_exists('Symfony\Component\ClassLoader\UniversalClassLoader', false)) {
    require_once __DIR__.'/vendor/Symfony/Component/ClassLoader/UniversalClassLoader.php';
}

use Symfony\Component\ClassLoader\UniversalClassLoader;

$loader = new UniversalClassLoader();
$loader->registerNamespaces(array(
    'Symfony' => __DIR__.'/vendor',
    'Buzz'    => __DIR__.'/vendor/Buzz/lib',
    'Goutte'  => __DIR__.'/src',
));
$loader->register();
