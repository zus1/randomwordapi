<?php


class DateHandler
{

    public function checkGreaterThenInterval(string $start, string $end, int $requiredDiff, $toTimestamp=true) {
        if($toTimestamp === true) {
            list($start, $end) = $this->convertDatesToTimestamp(array($start, $end));
        }

        if($end - $start > $requiredDiff) {
            return true;
        }

        return false;
    }

    public function calculateDateInterval(string $start, string $end, $toTimestamp=true) {
        if($toTimestamp === true) {
            list($start, $end) = $this->convertDatesToTimestamp(array($start, $end));
        }

        return $end - $start;
    }

    public function convertDatesToTimestamp(array $dates) {
        return array_map(function($date) {
            return $this->convertDateToTimestamp($date);
        }, $dates);
    }

    public function convertDateToTimestamp(string $date) {
        $dt = new DateTime($date);
        return $dt->getTimestamp();
    }
}