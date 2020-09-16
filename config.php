<?php

spl_autoload_register(function () {
    $classesRoot = $_SERVER['DOCUMENT_ROOT'] . "/classes";
    $apiRoot = $_SERVER['DOCUMENT_ROOT'] . "/api";
    $classFiles = scandir($classesRoot);
    $apiFiles = scandir($apiRoot);
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
});

