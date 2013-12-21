<?php

/**
 * Haste utilities for Contao Open Source CMS
 *
 * Copyright (C) 2012-2013 Codefog & terminal42 gmbh
 *
 * @package    Haste
 * @link       http://github.com/codefog/contao-haste/
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 */

namespace Haste\Form;

use Haste\Form\Validator\ValidatorInterface;
use Haste\Generator\RowClass;

class Form extends \Controller
{
    /**
     * State of the form
     * Can be either clean or dirty
     */
    const STATE_CLEAN = 0;
    const STATE_DIRTY = 1;

    /**
     * Form ID
     * @var string
     */
    protected $strFormId;

    /**
     * HTTP Method
     * @var string Can be either GET or POST
     */
    protected $strMethod;

    /**
     * Form action
     * @var string
     */
    protected $strFormAction;

    /**
     * True if the form has been submitted
     * @var boolean
     */
    protected $blnSubmitted;

    /**
     * Render forms tableless
     * @var boolean
     */
    protected $blnTableless = true;

    /**
     * True if the form has uploads
     * @var boolean
     */
    protected $blnHasUploads = false;

    /**
     * Form fields in the representation AFTER the Widget::getAttributesFromDca() call
     * @var array
     */
    protected $arrFormFields = array();

    /**
     * Widget instances
     * @var array
     */
    protected $arrWidgets = array();

    /**
     * Bound model
     * @var \Model
     */
    protected $objModel = null;

    /**
     * Validators
     * @var array
     */
    protected $arrValidators = array();

    /**
     * Enctype
     * @var string
     */
    protected $strEnctype = 'application/x-www-form-urlencoded';

    /**
     * Current form state
     * @var int
     */
    protected $intState = self::STATE_CLEAN;

    /**
     * Check if form is valid
     * @var boolean
     */
    protected $blnValid = true;

    /**
     * Initialize the form
     * @param   string   The ID of the form
     * @param   string   The HTTP Method GET or POST
     * @param   callable A callable that checks if the form has been submitted
     * @param   boolean Whether to render the form tableless or not
     * @throws  \InvalidArgumentException
     */
    public function __construct($strId, $strMethod, $varSubmitCheck, $blnTableless=true)
    {
        parent::__construct();

        if (is_numeric($strId)) {
            throw new \InvalidArgumentException('You cannot use a numeric form id.');
        }

        $this->strFormId = $strId;

        if (!in_array($strMethod, array('GET', 'POST'))) {
            throw new \InvalidArgumentException('The method has to be either GET or POST.');
        }

        if (!is_callable($varSubmitCheck)) {
            throw new \InvalidArgumentException('The submit check must be callable.');
        }

        $this->strMethod = $strMethod;
        $this->blnSubmitted = call_user_func($varSubmitCheck, $this);
        $this->blnTableless = $blnTableless;

        // The form action can be set using several helper methods but by default it's just
        // pointing to the current page
        $this->strFormAction = \Environment::get('request');
    }

    /**
     * Set the form action directly
     * @param   string  The URI
     * @return   Form
     */
    public function setFormActionFromUri($strUri)
    {
        $this->strFormAction = $strUri;

        return $this;
    }

    /**
     * Set the form action from a Contao page ID
     * @param   int  The page ID
     * @return   Form
     * @throws  \InvalidArgumentException
     */
    public function setFormActionFromPageId($intId)
    {
        if (($objPage = \PageModel::findByPk($intId)) === null) {
            throw new \InvalidArgumentException(sprintf('The page id "%s" does apparently not exist!', $intId));
        }

        $this->strFormAction = \Controller::generateFrontendUrl($objPage->row());

        return $this;
    }

    /**
     * Preserve the current GET parameters by adding them as hidden fields
     * @param array
     */
    public function preserveGetParameters($arrExclude=array())
    {
        foreach ($_GET as $k => $v) {
            if (in_array($k, $arrExclude)) {
                continue;
            }

            if (array_key_exists($k, $this->arrFormFields)) {
                continue;
            }

            $this->addFormField($k, array(
                'inputType' => 'hidden',
                'value' => \Input::get($k)
            ));
        }
    }

    /**
     * Get form method
     * @return  string
     */
    public function getMethod()
    {
        return $this->strMethod;
    }

    /**
     * Get the form action
     * @return  string
     */
    public function getFormAction()
    {
        return $this->strFormAction;
    }

    /**
     * Gets the form ID
     * @return  string The form ID
     */
    public function getFormId()
    {
        return $this->strFormId;
    }

    /**
     * Gets the encoding type
     * @return  string
     */
    public function getEnctype()
    {
        return $this->strEnctype;
    }

    /**
     * Check if the form has been submitted
     * @return  boolean
     */
    public function isSubmitted()
    {
        return (bool) $this->blnSubmitted;
    }

    /**
     * Check if the form is valid (no widget has an error)
     * @return  boolean
     */
    public function isValid()
    {
        return (bool) $this->blnValid;
    }

    /**
     * Check if form is dirty (widgets need to be generated)
     * return   bool
     */
    public function isDirty()
    {
        return (bool) ($this->intState === static::STATE_DIRTY);
    }

    /**
     * Check if there are uploads
     * @return  boolean
     */
    public function hasUploads()
    {
        // We need to create the widgets to know if we have uploads
        $this->createWidgets();

        return (bool) $this->blnHasUploads;
    }

    /**
     * Adds a form field
     * @param   string  The form field name
     * @param   array   The DCA representation of the field
     * @return  Form
     * @throws  \RuntimeException
     */
    public function addFormField($strName, array $arrDca)
    {
        $this->checkFormFieldNameIsValid($strName);

        // Make sure it has a "name" attribute because it is mandatory
        if (!isset($arrDca['name'])) {
            $arrDca['name'] = $strName;
        }

        // Support default values
        if (!$this->isSubmitted()) {
            if (isset($arrDca['default']) && !isset($arrDca['value'])) {
                $arrDca['value'] = $arrDca['default'];
            }

            // Try to load the default value from bound Model
            if ($this->objModel !== null) {
                $arrDca['value'] = $this->objModel->$strName;
            }
        }

        $strClass = $GLOBALS['TL_FFL'][$arrDca['inputType']];

        if (!class_exists($strClass)) {
            throw new \RuntimeException(sprintf('The class "%s" for type "%s" could not be found.', $strClass, $arrDca['inputType']));
        }

        // Convert date formats into timestamps
        if ($arrDca['eval']['rgxp'] == 'date' || $arrDca['eval']['rgxp'] == 'time' || $arrDca['eval']['rgxp'] == 'datim') {
            $this->addValidator($strName, function($varValue, $objWidget, $objForm) use ($arrDca) {
                if ($varValue != '') {
                    $objDate = new \Date($varValue, $GLOBALS['TL_CONFIG'][$arrDca['eval']['rgxp'] . 'Format']);
                    $varValue = $objDate->tstamp;
                }

                return $varValue;
            });
        }

        if (is_array($arrDca['save_callback'])) {
            $this->addValidator($strName, function($varValue, $objWidget, $objForm) use ($arrDca, $strName) {

                $intId = 0;
                $strTable = '';

                if (($objModel = $objForm->getBoundModel()) !== null) {
                    $intId = $objModel->id;
                    $strTable = $objModel->getTable();
                }

                $dc = (object) array(
                    'id'            => $intId,
                    'table'         => $strTable,
                    'value'         => $varValue,
                    'field'         => $strName,
                    'inputName'     => $objWidget->name,
                    'activeRecord'  => $objModel
                );

                foreach ($arrDca['save_callback'] as $callback) {
                    if (is_array($callback)) {
                        $objCallback = \System::importStatic($callback[0]);
                        $varValue = $objCallback->$callback[1]($varValue, $dc);
                    } elseif (is_callable($callback)) {
                        $varValue = $callback($varValue, $dc);
                    }
                }

                return $varValue;
            });
        }

        $arrDca = $strClass::getAttributesFromDca($arrDca, $arrDca['name'], $arrDca['value']);

        $this->arrFormFields[$strName] = $arrDca;
        $this->intState = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Add multiple form fields
     * @param   array
     * @return  Form
     */
    public function addFormFields($arrFormFields)
    {
        foreach ($arrFormFields as $strName => $arrDca) {
            $this->addFormField($strName, $arrDca);
        }

        return $this;
    }

    /**
     * Binds a model instance to the form. If there is data, haste form will add
     * the present values as default values.
     * @param   \Model
     */
    public function bindModel(\Model $objModel=null)
    {
        $this->objModel = $objModel;
    }

    /**
     * Gets the bound model
     * @return   \Model
     */
    public function getBoundModel()
    {
        return $this->objModel;
    }

    /**
     * Helper method to easily add a captcha field
     * @param   string  The form field name
     */
    public function addCaptchaFormField($strName)
    {
        $this->addFormField($strName, array(
            'name'      => $strName . '_' . $this->strFormId, // make sure they're unique on a page
            'label'     => $GLOBALS['TL_LANG']['MSC']['securityQuestion'],
            'inputType' => 'captcha',
            'eval'      => array('mandatory'=>true)
        ));
    }

    /**
     * Helper method to easily add a submit field
     * @param   string  The form field name
     * @param   string  The label for the submit button
     */
    public function addSubmitFormField($strName, $strLabel)
    {
        $this->addFormField($strName, array(
            'name'      => $strName,
            'label'     => $strLabel,
            'inputType' => 'submit'
        ));
    }

    /**
     * Add form fields from a back end DCA
     * @param   string   The DCA table name
     * @param   callable Called for each field, return true if you want to include the field in the form
     * @return  Form
     */
    public function addFieldsFromDca($strTable, $varCallback = null)
    {
        \System::loadLanguageFile($strTable);
        $this->loadDataContainer($strTable);
        $arrFields = &$GLOBALS['TL_DCA'][$strTable]['fields'];

        foreach ($arrFields as $k => $v) {
            if (is_callable($varCallback) && !call_user_func_array($varCallback, array(&$k, &$v))) {
                continue;
            }

            $this->addFormField($k, $v);
        }

        return $this;
    }

    /**
     * Add form fields from a back end form generator form ID
     * @param   int      The form generator form ID
     * @param   callable Called for each field, return true if you want to include the field in the form
     * @return  Form
     * @throws  \InvalidArgumentException
     */
    public function addFieldsFromFormGenerator($intId, $varCallback = null)
    {
        if (($objFields = \FormFieldModel::findPublishedByPid($intId)) === null) {
            throw new \InvalidArgumentException('Form ID "' . $intId . '" does not exist or has no published fields.');
        }

        $arrFields = array();

        while ($objFields->next()) {
            // make sure "name" is set because not all form fields do need it and it would thus overwrite the array indexes
            $strName = $objFields->name ?: 'field_' . $objFields->id;

            $arrDca = $objFields->row();

            // Make sure it has a "name" attribute because it is mandatory
            if (!isset($arrDca['name'])) {
                $arrDca['name'] = $strName;
            }

            $arrFields[$strName] = $arrDca;
        }

        foreach ($arrFields as $k => $v) {

            if (is_callable($varCallback) && !call_user_func_array($varCallback, array(&$k, &$v))) {
                continue;
            }

            $this->arrFormFields[$k] = $v;
        }

        $this->intState = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Get a form field by a given name
     * @param   string  The form field name
     * @return  array
     */
    public function getFormField($strName)
    {
        return $this->arrFormFields[$strName];
    }

    /**
     * Get all form fields
     * @return  array
     */
    public function getFormFields()
    {
        return $this->arrFormFields;
    }

    /**
     * Removes a form field
     * @param   string  The form field name
     * @return  Form
     */
    public function removeFormField($strName)
    {
        unset($this->arrFormFields[$strName]);
        $this->intState = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Checks if there is a form field with a given name
     * @param   string  The form field name
     */
    public function hasFormField($strName)
    {
        return isset($this->arrFormFields[$strName]);
    }

    /**
     * Returns a widget instance if existing
     * @param   string  The form field name
     * @return  \Widget
     */
    public function getWidget($strName)
    {
        $this->createWidgets();

        return $this->arrWidgets[$strName];
    }

    /**
     * Return all widgets
     * @return  array
     */
    public function getWidgets()
    {
        $this->createWidgets();

        return $this->arrWidgets;
    }

    /**
     * Add a validator to the form field
     * @param   string   The form field name
     * @param   ValidatorInterface|callable An instance of ValidatorInterface or a callable that will be called on widget validation
     * @return  Form
     * @throws  \InvalidArgumentException
     */
    public function addValidator($strName, $varValidator)
    {
        if ($varValidator instanceof ValidatorInterface || is_callable($varValidator)) {
            $this->arrValidators[$strName][] = $varValidator;
        } else {
            throw new \InvalidArgumentException('Your validator is invalid!');
        }

        return $this;
    }

    /**
     * Create the widget instances
     * @return  Form
     * @throws  \RuntimeException
     */
    public function createWidgets()
    {
        // Do nothing if already generated
        if (!$this->isDirty()) {
            return;
        }

        $intTotal = count($this->arrFormFields);
        $i = 0;

        // Reset to initial values
        $this->arrWidgets = array();
        $this->blnHasUploads = false;

        // Initialize widgets
        foreach ($this->arrFormFields as $strName => $arrField) {

            $strClass = $GLOBALS['TL_FFL'][$arrField['type']];

            if (!class_exists($strClass)) {
                throw new \RuntimeException(sprintf('The class "%s" for type "%s" could not be found.', $strClass, $arrField['type']));
            }

            $arrField['tableless']  = $this->blnTableless;

            // Some widgets render the mandatory asterisk only based on "require" attribute
            if (!isset($arrField['required'])) {
                $arrField['required'] = (bool) $arrField['mandatory'];
            }

            $objWidget = new $strClass($arrField);

            if ($objWidget instanceof \uploadable) {
                $this->blnHasUploads = true;
            }

            $this->arrWidgets[$strName] = $objWidget;
            $i++;
        }

        RowClass::withKey('rowClass')->addCount('row_')->addFirstLast('row_')->addEvenOdd()->applyTo($this->arrWidgets);

        $this->intState = self::STATE_CLEAN;

        if ($this->hasUploads()) {
            if ($this->getMethod() == 'GET') {
                throw new \RuntimeException('How do you want me to upload your file using GET?');
            }

            $this->strEnctype = 'multipart/form-data';
        } else {
            $this->strEnctype = 'application/x-www-form-urlencoded';
        }

        return $this;
    }

    /**
     * Validate the form
     * @return boolean
     */
    public function validate()
    {
        if (!$this->isSubmitted()) {
            return false;
        }

        $this->createWidgets();
        $this->blnValid = true;

        foreach ($this->arrWidgets as $strName => $objWidget) {
            $objWidget->validate();

            if ($objWidget->hasErrors()) {
                $this->blnValid = false;

            } elseif ($objWidget->submitInput()) {

                $varValue = $objWidget->value;

                // Run custom validators
                if (isset($this->arrValidators[$strName])) {

                    try {
                        foreach ($this->arrValidators[$strName] as $varValidator) {

                            if ($varValidator instanceof ValidatorInterface) {
                                $varValue = $varValidator->validate($varValue, $objWidget, $this);
                            } else {
                                $varValue = call_user_func($varValidator, $varValue, $objWidget, $this);
                            }
                        }
                    } catch (\Exception $e) {
                        $objWidget->class = 'error';
                        $objWidget->addError($e->getMessage());
                    }
                }

                // Bind to Model instance
                if (!$objWidget->hasErrors() && $this->objModel !== null) {
                    $this->objModel->$strName =  $varValue;
                }
            }
        }

        return $this->isValid();
    }

    /**
     * Add form to a template
     * @param   FrontendTemplate
     * @return  Form
     */
    public function addToTemplate(\FrontendTemplate $objTemplate)
    {
        $this->createWidgets();

        $objTemplate->action = $this->getFormAction();
        $objTemplate->formId = $this->getFormId();
        $objTemplate->method = $this->getMethod();
        $objTemplate->enctype = $this->getEnctype();
        $objTemplate->widgets = $this->arrWidgets;
        $objTemplate->valid = $this->isValid();
        $objTemplate->submitted = $this->isSubmitted();
        $objTemplate->hasUploads = $this->hasUploads();
        $objTemplate->tableless = $this->blnTableless;

        $arrWidgets = $this->splitHiddenAndVisibleWidgets();

        // Generate hidden form fields
        foreach ((array) $arrWidgets['hidden'] as $objWidget) {
            $objTemplate->hidden .= $objWidget->parse();
        }

        // Generate visible form fields
        foreach ((array) $arrWidgets['visible'] as $objWidget) {
            $objTemplate->fields .= $objWidget->parse();
        }

        $objTemplate->hiddenWidgets  = $arrWidgets['hidden'];
        $objTemplate->visibleWidgets = $arrWidgets['visible'];

        $objTemplate->hasteFormInstance = $this;

        return $this;
    }

    /**
     * Generate a form and return it as HTML string
     * @return string
     */
    public function generate()
    {
        $this->createWidgets();

        $objTemplate = new \FrontendTemplate('form');
        $objTemplate->class = 'hasteform_' . $this->getFormId();
        $objTemplate->tableless = $this->blnTableless;
        $objTemplate->action = $this->getFormAction();
        $objTemplate->formId = $this->getFormId();
        $objTemplate->method = strtolower($this->getMethod());
        $objTemplate->enctype = $this->getEnctype();
        $objTemplate->formSubmit = $this->getFormId();

        $arrWidgets = $this->splitHiddenAndVisibleWidgets();

        // Generate hidden form fields
        foreach ((array) $arrWidgets['hidden'] as $objWidget) {
            $objTemplate->hidden .= $objWidget->parse();
        }

        // Generate visible form fields
        foreach ((array) $arrWidgets['visible'] as $objWidget) {
            $objTemplate->fields .= $objWidget->parse();
        }

        return $objTemplate->parse();
    }

    /**
     * Return the submitted data of a specific form field
     * @param   string   The form field name
     * @return  mixed    The value of the widget
     * @throws  \BadMethodCallException
     * @throws  \InvalidArgumentException
     */
    public function fetch($strName)
    {
        if (!$this->isSubmitted()) {
            throw new \BadMethodCallException('How do you want me to fetch data from an unsubmitted form?');
        }

        if ($this->getMethod() !== 'POST') {
            throw new \BadMethodCallException('Widgets only support fetching POST values. Use the Contao Input class for other purposes.');
        }

        if (!isset($this->arrWidgets[$strName])) {
            throw new \InvalidArgumentException('The widget with name "' . $strName . '" does not exist.');
        }

        return $this->arrWidgets[$strName]->value;
    }

    /**
     * Return the submitted data as an associative array
     * @param   callable    A callable that should be used to fetch the data instead of the built in functionality
     * @return  array
     */
    public function fetchAll($varCallback=null)
    {
        $arrData = array();

        foreach ($this->arrWidgets as $strName => $objWidget) {
            if (is_callable($varCallback)) {
                $arrData[$strName] = call_user_func($varCallback, $strName, $objWidget);
            } else {
                $arrData[$strName] = $this->fetch($strName);
            }
        }

        return $arrData;
    }

    /**
     * Generate a form and return it as HTML string
     * @return string
     */
    public function __toString()
    {
        return $this->generate();
    }

    /**
     * Check for a valid form field name
     * @param   string  The form field name
     * @throws  \InvalidArgumentException
     */
    protected function checkFormFieldNameIsValid($strName)
    {
        if (is_numeric($strName)) {
            throw new \InvalidArgumentException('You cannot use a numeric form field name.');
        }

        if (in_array($strName, $this->arrFormFields)) {
            throw new \InvalidArgumentException(sprintf('"%s" has already been added to the form.', $strName));
        }
    }

    /**
     * Splits hidden and visible widgets
     * @return array
     */
    protected function splitHiddenAndVisibleWidgets()
    {
        $arrResult = array();
        foreach ($this->arrWidgets as $k => $objWidget) {
            $strKey = ($objWidget instanceof \FormHidden) ? 'hidden' : 'visible';
            $arrResult[$strKey][$k] = $objWidget;
        }

        return $arrResult;
    }
}