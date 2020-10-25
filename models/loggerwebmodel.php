<?php


class LoggerWebModel extends Model
{
    protected $idField = 'id';
    protected $table = 'log_web';
    protected $dataSet = array(
        "id", "message", "code", "line", "trace", "file", "type"
    );
}