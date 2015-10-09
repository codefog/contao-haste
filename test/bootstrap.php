<?php

error_reporting(E_ERROR);

$GLOBALS['TL_FFL']['text'] = 'FormTextField';

class Widget
{
    protected $arrData = array();
    protected $varValue = null;

    public function __construct($arrData)
    {
        foreach ($arrData as $k => $v) {
            $this->$k = $v;
        }
    }

    public function __get($key)
    {
        switch ($key) {
            case 'value':
                return $this->varValue;
        }

        return $this->$key;
    }

    public function __set($key, $value)
    {
        switch ($key) {
            case 'value':
                $this->varValue = $value;
                break;
            default:
                $this->$key = $value;
        }
    }

    public function validate()
    {
        $varValue = $this->validator(\Input::post($this->name));
        $this->value = $varValue;
        return true;
    }

    public function validator($value)
    {
        return $value;
    }

    public function hasErrors()
    {
        return false;
    }

    public function submitInput()
    {
        return true;
    }

    public function addError($error)
    {
        // I am a dummy
    }

    public static function getAttributesFromDca($arrDca)
    {
        $arrDca['type'] = $arrDca['inputType'];
        return $arrDca;
    }
}

class FormTextField extends Widget {}
class TextField extends Widget {}

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
        return $strName;
    }

    public function replaceInsertTags($strText)
    {
        return $strText;
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

class Input
{
    public static function get($key)
    {
        return $_GET[$key];
    }
    public static function setGet($key, $value)
    {
        $_GET[$key] = $value;
    }
    public static function post($key)
    {
        return $_POST[$key];
    }
    public static function setPost($key, $value)
    {
        $_POST[$key] = $value;
    }
}

class Model {}

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

function standardize($varValue) {
    return $varValue;
}
function ampersand($strString, $blnEncode=true)
{
    return preg_replace('/&(amp;)?/i', ($blnEncode ? '&amp;' : '&'), $strString);
}
function array_is_assoc($arrArray)
{
    return (is_array($arrArray) && array_keys($arrArray) !== range(0, (sizeof($arrArray) - 1)));
}
