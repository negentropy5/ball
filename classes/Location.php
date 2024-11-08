<?php
class Location
{
    public static function l($str) {
        $host = $_SERVER['HTTP_HOST'];
        $url = rtrim(dirname($_SERVER['PHP_SELF']), '/');
        header("Location: //$host$url/$str");
        exit;
    }
}