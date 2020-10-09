<?php


class IpCheckerModel extends Model
{
    protected $idField = 'id';
    protected $table = 'ip';
    protected $dataSet = array(
        "id", "ip_address", "total_requests", "requests_period", "period_start", "period_end", "hard_banned", "soft_banned",
        "soft_banned_at", "hard_banned_at", "period_limit", "soft_ban_count"
    );
}