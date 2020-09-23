<?php

spl_autoload_register(function () {
    $classesRoot = $_SERVER['DOCUMENT_ROOT'] . "/classes";
    $apiRoot = $_SERVER['DOCUMENT_ROOT'] . "/api";
    $configRoot = $_SERVER['DOCUMENT_ROOT'] . "/config";
    $classFiles = scandir($classesRoot);
    $apiFiles = scandir($apiRoot);
    $configFiles = scandir($configRoot);
    foreach($classFiles as $file) {
        if($file !== "." && $file !== "..") {
            include_once($classesRoot . "/" . $file);
        }
    }
    foreach($apiFiles as $file) {
        if($file !== "." && $file !== "..") {
            include_once($apiRoot . "/" . $file);
        }
    }
    foreach($configFiles as $file) {
        if($file !== "." && $file !== "..") {
            include_once($configRoot . "/" . $file);
        }
    }
});

