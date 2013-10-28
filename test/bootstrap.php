<?php

class Controller
{
    public function __construct()
    {
        // I am a dummy
    }
    public function loadDataContainer($strName)
    {
        // I am a dummy
    }
    public function generateFrontendUrl($strName)
    {
        // I am a dummy
    }
}
class Database_Result
{
    public function row()
    {
        return array();
    }
}

class Environment
{
    public static function get($strParam)
    {
        // I am a dummy
    }
}

$GLOBALS['objPage'] = new Database_Result();

include_once __DIR__ . '/../library/Haste/Form.php';