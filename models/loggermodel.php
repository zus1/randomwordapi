<?php


class LoggerModel extends Model
{
    protected $idField = 'id';
    protected $table = 'log';
    protected $dataSet = array(
        "id", "message", "code", "line", "trace", "file", "created_at", "type"
    );
}