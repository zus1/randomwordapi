<?php

class Router
{
    const REQUEST_POST = 'post';
    const REQUEST_GET = "get";

    private $supportedRequestMethods = array(self::REQUEST_GET, self::REQUEST_POST);

    public function webRoutes() {
        return array(
            '/views/adm/insert.php',
            '/views/adm/modify.php',
            '/views/documentation.php'
        );
    }

    public function apiRoutes() {
        return array(
            '/api/v1/generate.php'
        );
    }

    public function routeMapping() {
        return array(
            '/views/adm/insert.php' => array('class' => Controller::class, 'method' => 'adminAddWords', 'request' => self::REQUEST_GET, 'api' => false, 'json' => false, 'view' => true, 'auth' => true),
            '/views/adm/modify.php' => array('class' => Controller::class, 'method' => 'adminModifyWords', 'request' => self::REQUEST_GET, 'api' => false, 'json' => false, 'view' => true, 'auth' => true),
            '/views/documentation.php' => array('class' => Controller::class, 'method' => 'webApiDocs', 'request' => self::REQUEST_GET, 'api' => false, 'json' => false, 'view' => true, 'auth' => false),
            '/views/auth/login.php' => array('class' => Controller::class, 'method' => 'login', 'request' => self::REQUEST_POST, 'api' => false, 'json' => false, 'view' => true, 'auth' => false),
            '/api/v1/generate.php' => array('class' => ApiController::class, 'method' => 'generateWords', 'request' => self::REQUEST_GET, 'api' => true, 'json' => true, 'view' => false, 'auth' => false),
        );
    }

    public function route() {

    }
}