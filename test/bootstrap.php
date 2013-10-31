<?php

error_reporting(E_ERROR);

$GLOBALS['TL_FFL']['text'] = 'FormTextField';

class FormTextField
{
    public $name;
    public $value;

    public function __construct($arrData)
    {
        $this->name = $arrData['name'];
        $this->value = $arrData['value'];
    }

    public function validate()
    {
        $this->value = $_POST[$this->name];
        return true;
    }

    public function hasErrors()
    {
        return false;
    }

    public function submitInput()
    {
        return true;
    }

    public static function getAttributesFromDca($arrDca)
    {
        $arrDca['type'] = $arrDca['inputType'];
        return $arrDca;
    }
}

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

class Model
{

}

class PageModel extends Model
{
    public $id;
    public $pageTitle;
    public $jumpTo;

    public static function findByPk($id)
    {
        $objModel = new static();
        $objModel->id = $id;
        $objModel->pageTitle = 'My page';
        $objModel->jumpTo = 11;
        return $objModel;
    }
}

$GLOBALS['objPage'] = new Database_Result();

include_once __DIR__ . '/../library/Haste/Form.php';