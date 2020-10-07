<?php


class ApiApp
{
    private $apiValidator;

    public function __construct(ApiValidator $validator) {
        $this->apiValidator = $validator;
    }

    private $dataSet = array(
        "id", "name", "user_id", "access_token", "first_request", "rate_limit", "requests_spent",
        "requests_remaining", "time_left_until_reset", "deactivated", "soft_banned", "hard_banned", "limit_reached"
    );

    public function getApiApp(?array $fields=array(), ?array $where=array()) {
        $invalidFields = array_diff($fields, $this->dataSet);
        $invalidWhereKeys = array_diff(array_keys($where), $this->dataSet);
        if(!empty($invalidFields)) {
            throw new Exception("Invalid fields for app select", HttpCodes::INTERNAL_SERVER_ERROR);
        }
        if(!empty($invalidWhereKeys)) {
            throw new Exception("Invalid where fields for app select", HttpCodes::INTERNAL_SERVER_ERROR);
        }

        if(empty($fields)) {
            $fields = $this->dataSet;
        }

        $query = "SELECT ";
        $fields = array_map(function($value) {
            return $this->apiValidator->filterAlphaDash($value);
        }, $fields);
        $fieldsStr = implode(",", $fields);
        $query .= $fieldsStr . " FROM api_app";

        $values = array();
        if(!empty($where)) {
            $query .= " WHERE";
            $keys = array_keys($where);
            $values = array_values($where);
            $keys = array_map(function($value) {
                return $this->apiValidator->filterAlphaDash($value);
            }, $keys);
            array_walk($keys, function ($key) use(&$query) {
                $query .= sprintf(" %s=? AND", $key);
            });
            $query = substr($query, 0, strlen($query) - strlen(" AND"));
        }
        /*var_dump($values);
        die();*/
        //return $query;
        return Factory::getObject(Factory::TYPE_DATABASE, true)->select($query, array(), $values);
    }
}