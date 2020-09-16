<?php

class Database
{
    private $pdo = null;
    private $typeToPdoMapping = array(
        'string' => PDO::PARAM_STR,
        'integer' => PDO::PARAM_INT
    );

    public function __construct() {
        if(is_null($this->pdo)) {
            $this->initDatabase();
        }
    }

    private function initDatabase() {
        $initFile = HttpParser::root() . "/init.ini";
        if(!file_exists($initFile)) {
            throw new Exception("No init file");
        }
        $initVariables = parse_ini_file($initFile);
        $username = $initVariables["DB_USERNAME"];
        $password = $initVariables["DB_PASSWORD"];
        $host = $initVariables["DB_HOST"];
        $db = $initVariables["DB_NAME"];
        $charset = $initVariables["DB_CHARSET"];

        $dsn = sprintf("mysql:dbname=%s;host=%s;charset=%s;",$db, $host, $charset);
        try {
            $pdo = new PDO($dsn, $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo = $pdo;
        } catch (PDOException $e) {
            //TODO batter handling maybe?
            throw $e;
        }
    }

    public function execute($query, $types, $params) {
        $sth = $this->pdo->prepare($query);
        $this->bindParams($sth, $params, $types);
        $sth->execute();
    }

    public function select($query, $types, $params, $assoc = true) {
        $sth = $this->pdo->prepare($query);
        $this->bindParams($sth, $params, $types);
        if($assoc === true) {
            $sth->setFetchMode(PDO::FETCH_ASSOC);
        } else {
            $sth->setFetchMode(PDO::FETCH_FUNC);
        }
        $sth->execute();

        return $sth->fetchAll();
    }

    private function bindParams($sth, array $params, array $types) {
        for($i = 1; $i <= count($params); $i++) {
            $sth->bindParam($i, $params[$i -1], $this->typeToPdoMapping[$types[$i -1]]);
        }
    }
}