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
        $username = Config::get(Config::DB_USERNAME, "");
        $password = Config::get(Config::DB_PASSWORD, "");
        $host = Config::get(Config::DB_HOST, "");
        $db = Config::get(Config::DB_NAME, "");
        $charset = Config::get(Config::DB_CHARSET, "");

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

    public function select(string $query, ?array $types=array(), ?array $params = array(), $assoc = true) {
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

    public function buildUpdateQuery(array $uFields, ?array $wFields=array()) {
        $query = "UPDATE user SET ";
        foreach($uFields as $field) {
            $query .= sprintf("%s=?,", $field);
        }

        $query = substr($query, 0, strlen($query) - 1);
        if(!empty($wFields)) {
            $query = $this->addWhere($wFields, $query);
        }

        return $query;
    }

    private function addWhere(array $wFields, string $query) {
        $query .= " WHERE";
        foreach($wFields as $wField) {
            $query .= sprintf(" %s=? AND", $wField);
        }
        return substr($query, 0, strlen($query) - strlen(" AND"));
    }

    private function bindParams($sth, array $params, array $types) {
        for($i = 1; $i <= count($params); $i++) {
            if(!empty($types)) {
                $sth->bindParam($i, $params[$i -1], $this->typeToPdoMapping[$types[$i -1]]);
            } else {
                $sth->bindParam($i, $params[$i -1]);
            }
        }
    }

}