<?php

spl_autoload_register(function () {
    $root = $_SERVER["DOCUMENT_ROOT"];
    $paths = array(
        $root . "/classes",
        $root . "/api",
        $root . "/config",
        $root . "/classes/words",
        $root . "/extenders"
    );

    foreach($paths as $path) {
        $files = scandir($path);
        foreach($files as $file) {
            if($file != "." && $file !== "..") {
                if(!is_dir($path . "/" . $file)) {
                    include_once($path . "/" . $file);
                }
            }
        }
    }
});

