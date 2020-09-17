<?php

class Router
{
    const REQUEST_POST = 'post';
    const REQUEST_GET = "get";
    private $user;

    private $supportedRequestMethods = array(self::REQUEST_GET, self::REQUEST_POST);

    public function __construct(User $user) {
        $this->user = $user;
    }

    public function webRoutes() {
        return array(
            '/views/adm/insert.php',
            '/views/adm/modify.php',
            '/views/documentation.php',
            '/views/auth/dologin.php',
            '/views/auth/login.php',
            '/views/error.php'
        );
    }

    public function apiRoutes() {
        return array(
            '/api/v1/generate.php'
        );
    }

    public function routeMapping() {
        return array(
            '/views/adm/insert.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminAddWords', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/adm/modify.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'adminModifyWords', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => true),
            '/views/documentation.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'webApiDocs', 'request' => self::REQUEST_GET, 'role' => "admin", 'auth' => false),
            '/views/auth/login.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'login', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
            '/views/auth/dologin.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'doLogin', 'request' => self::REQUEST_POST, 'role' => "", 'auth' => false),
            '/views/error.php' => array('class' => Factory::TYPE_CONTROLLER, 'method' => 'error', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
        );
    }

    public function apiRouteMapping() {
        return array(
            '/api/v1/generate.php' => array('class' => Factory::TYPE_API_CONTROLLER, 'method' => 'generateWords', 'request' => self::REQUEST_GET, 'role' => "", 'auth' => false),
        );
    }

    public function routeAll() {
        $requestUri = explode("?", strtolower($_SERVER["REQUEST_URI"]))[0];
        if(in_array($requestUri, $this->webRoutes())) {
            $this->route($requestUri);
        } else {
             $this->routeApi($requestUri);
        }
    }

    public function route($requestUri) {
        $requestMethod = strtolower($_SERVER["REQUEST_METHOD"]);
        $routes = $this->routeMapping();

        $route = array();
        try {
            $route = $this->validateRequest($requestUri, $routes, $requestMethod);
        } catch(Exception $e) {
            $this->redirect(HttpParser::baseUrl() . "views/error.php?error=" . $e->getMessage() . "&code=" . $e->getCode(), $e->getCode());
        }

        $result = "";
        try {
            $result = call_user_func([Factory::getObject($route['class']), $route['method']]);
        } catch(Exception $e) {
            $this->redirect(HttpParser::baseUrl() . "views/error.php?error=" . $e->getMessage() . "&code=" . $e->getCode(), $e->getCode());
        }

        $this->returnResult($result);
    }

    public function routeApi($requestUri) {
        $requestMethod = strtolower($_SERVER["REQUEST_METHOD"]);
        $routes = $this->apiRouteMapping();

        try {
            $route = $this->validateRequest($requestUri, $routes, $requestMethod);
        } catch(Exception $e) {
            echo Factory::getObject(Factory::TYPE_API_EXCEPTION)->getApiException($e);
            die();
        }

        try {
            $result = json_encode(call_user_func([Factory::getObject($route['class']), $route['method']]));
        } catch(Exception $e) {
            echo Factory::getObject(Factory::TYPE_API_EXCEPTION)->getApiException($e);
            die();
        }

        $this->returnResult($result);
    }

    private function validateRequest(string $requestUri, array $routes, string $requestMethod) {
        if(!array_key_exists($requestUri, $routes)) {
            throw new Exception("Page do not exists", HttpCodes::HTTP_NOT_FOUND);
        }
        $route = $routes[$requestUri];
        if(!in_array($route['request'], $this->supportedRequestMethods)) {
            throw new Exception("Method not supported", HttpCodes::METHOD_NOT_ALLOWED);
        }
        if($requestMethod !== $route['request']) {
            throw new Exception("Method invalid", HttpCodes::HTTP_FORBIDDEN);
        }

        if($route['auth'] === true) {
            if(!$this->user->isAuthenticatedUser()) {
                $this->redirect(HttpParser::baseUrl() . "views/auth/login.php", HttpCodes::HTTP_FORBIDDEN);
            }
        }
        if(!empty($route["role"])) {
            if(!$this->user->hasRole($route['role'])) {
                throw new Exception("Forbidden", HttpCodes::HTTP_FORBIDDEN);
            }
        }

        return $route;
    }

    private function returnResult(string $result) {
        http_response_code(HttpCodes::HTTP_ACCEPTED);
        echo $result;
    }

    public function redirect(string $url, ?int $code=null) {
        $code = ($code)? $code : HttpCodes::HTTP_OK;
        http_response_code($code);
        header("Location: " . $url);
        die();
    }
}