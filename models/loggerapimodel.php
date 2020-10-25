<?php


class LoggerApiModel extends Model
{
    protected $idField = 'id';
    protected $table = 'log_api';
    protected $dataSet = array(
        "id", "message", "code", "line", "trace", "file", "created_at", "type"
    );
}