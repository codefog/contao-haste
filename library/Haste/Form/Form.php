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
use Haste\Util\ArrayPosition;

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
     * True if the HTML5 validation should be ignored
     * @var bool
     */
    protected $blnNoValidate = false;

    /**
     * Form fields in the representation AFTER the Widget::getAttributesFromDca() call
     * @var array
     */
    protected $arrFormFields = array();

    /**
     * Widget instances
     * @var \Widget[]
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
     *
     * @param string   $strId          The ID of the form
     * @param string   $strMethod      The HTTP Method GET or POST
     * @param callable $varSubmitCheck A callable that checks if the form has been submitted
     * @param boolean  $blnTableless   Whether to render the form tableless or not
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($strId, $strMethod, $varSubmitCheck, $blnTableless = true)
    {
        parent::__construct();

        if (is_numeric($strId)) {
            throw new \InvalidArgumentException('You cannot use a numeric form id.');
        }

        $this->strFormId = $strId;

        if (!in_array($strMethod, array('GET', 'POST'), true)) {
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
     *
     * @param string $strUri The URI
     *
     * @return $this
     */
    public function setFormActionFromUri($strUri)
    {
        $this->strFormAction = $strUri;

        return $this;
    }

    /**
     * Set the form action from a Contao page ID
     *
     * @param   int $intId The page ID
     *
     * @return   $this
     * @throws  \InvalidArgumentException
     */
    public function setFormActionFromPageId($intId)
    {
        if (($objPage = \PageModel::findWithDetails($intId)) === null) {
            throw new \InvalidArgumentException(sprintf('The page id "%s" does apparently not exist!', $intId));
        }

        $this->strFormAction = \Controller::generateFrontendUrl($objPage->row(), null, $objPage->language);

        return $this;
    }

    /**
     * Preserve the current GET parameters by adding them as hidden fields
     *
     * @param array $arrExclude
     */
    public function preserveGetParameters($arrExclude = array())
    {
        foreach ($_GET as $k => $v) {
            if (in_array($k, $arrExclude, false)) {
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
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->strMethod;
    }

    /**
     * Get the form action
     *
     * @return string
     */
    public function getFormAction()
    {
        return $this->strFormAction;
    }

    /**
     * Gets the form ID
     *
     * @return string The form ID
     */
    public function getFormId()
    {
        return $this->strFormId;
    }

    /**
     * Gets the encoding type
     * 
     * @return  string
     */
    public function getEnctype()
    {
        return $this->strEnctype;
    }

    /**
     * Get novalidate flag
     * 
     * @return bool
     */
    public function isNoValidate()
    {
        return $this->blnNoValidate;
    }

    /**
     * Generate the novalidate attribute
     * 
     * @return string
     */
    public function generateNoValidate()
    {
        return $this->isNoValidate() ? ' novalidate' : '';
    }

    /**
     * Set novalidate flag
     *
     * @param bool $blnNoValidate
     */
    public function setNoValidate($blnNoValidate)
    {
        $this->blnNoValidate = (bool) $blnNoValidate;
    }

    /**
     * Get tableless flag
     *
     * @return bool
     */
    public function isTableless()
    {
        return $this->blnTableless;
    }

    /**
     * Set tabeless flag
     *
     * @param bool $blnTableless
     *
     * @return $this
     */
    public function setTableless($blnTableless)
    {
        $this->blnTableless = (bool) $blnTableless;
        $this->intState = self::STATE_DIRTY;

        return $this;
    }


    /**
     * Check if the form has been submitted
     *
     * @return bool
     */
    public function isSubmitted()
    {
        return (bool) $this->blnSubmitted;
    }

    /**
     * Check if the form is valid (no widget has an error)
     *
     * @return  bool
     */
    public function isValid()
    {
        return (bool) $this->blnValid;
    }

    /**
     * Check if form is dirty (widgets need to be generated)
     *
     * @return bool
     */
    public function isDirty()
    {
        return (bool) ($this->intState === static::STATE_DIRTY);
    }

    /**
     * Check if there are uploads
     *
     * @return  bool
     */
    public function hasUploads()
    {
        // We need to create the widgets to know if we have uploads
        $this->createWidgets();

        return (bool) $this->blnHasUploads;
    }

    /**
     * Check if form has fields
     *
     * @return bool
     */
    public function hasFields()
    {
        return !empty($this->arrFormFields);
    }

    /**
     * Adds a form field
     *
     * @param string        $strName the form field name
     * @param array         $arrDca The DCA representation of the field
     * @param ArrayPosition $position
     *
     * @return $this
     */
    public function addFormField($strName, array $arrDca, ArrayPosition $position = null)
    {
        $this->checkFormFieldNameIsValid($strName);

        if (null === $position) {
            $position = ArrayPosition::last();
        }

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
            if (!$arrDca['ignoreModelValue'] && $this->objModel !== null) {
                $arrDca['value'] = $this->objModel->$strName;
            }
        }

        if (!isset($arrDca['inputType'])) {
            throw new \RuntimeException(sprintf('You did not specify any inputType for the field "%s"!', $strName));
        }

        /** @type \Widget $strClass */
        $strClass = $GLOBALS['TL_FFL'][$arrDca['inputType']];

        if (!class_exists($strClass)) {
            throw new \RuntimeException(sprintf('The class "%s" for type "%s" could not be found.', $strClass, $arrDca['inputType']));
        }

        // Convert date formats into timestamps
        $rgxp = $arrDca['eval']['rgxp'];
        if (in_array($rgxp, array('date', 'time', 'datim'), true)) {
            $this->addValidator($strName, function($varValue) use ($rgxp) {
                if ($varValue != '') {
                    $key = $rgxp . 'Format';
                    $format = isset($GLOBALS['objPage']) ? $GLOBALS['objPage']->{$key} : $GLOBALS['TL_CONFIG'][$key];
                    $objDate = new \Date($varValue, $format);
                    $varValue = $objDate->tstamp;
                }

                return $varValue;
            });
        }

        if (is_array($arrDca['save_callback'])) {

            $this->addValidator(
                $strName,
                function($varValue, \Widget $objWidget, Form $objForm) use ($arrDca, $strName) {
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
                            $varValue = $objCallback->{$callback[1]}($varValue, $dc);
                        } elseif (is_callable($callback)) {
                            $varValue = $callback($varValue, $dc);
                        }
                    }

                    return $varValue;
                }
            );
        }

        $arrDca = $strClass::getAttributesFromDca($arrDca, $arrDca['name'], $arrDca['value']);

        // Convert optgroups so they work with FormSelectMenu
        if (is_array($arrDca['options']) && array_is_assoc($arrDca['options'])) {
            $arrOptions = $arrDca['options'];
            $arrDca['options'] = array();

            foreach ($arrOptions as $k => $v) {
                if (isset($v['label'])) {
                    $arrDca['options'][] = $v;
                } else {
                    $arrDca['options'][] = array(
                        'label'     => $k,
                        'value'     => $k,
                        'group'     => '1',
                    );

                    foreach ($v as $vv) {
                        $arrDca['options'][] = $vv;
                    }
                }
            }
        }

        $this->arrFormFields = $position->addToArray($this->arrFormFields, array($strName=>$arrDca));
        $this->intState = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Add multiple form fields
     *
     * @param array         $arrFormFields
     * @param ArrayPosition $position
     *
     * @return $this
     */
    public function addFormFields(array $arrFormFields, ArrayPosition $position = null)
    {
        if (null !== $position && ($position->position() === ArrayPosition::FIRST || $position->position() === ArrayPosition::BEFORE)) {
            $arrFormFields = array_reverse($arrFormFields, true);
        }

        foreach ($arrFormFields as $strName => $arrDca) {
            $this->addFormField($strName, $arrDca, $position);
        }

        return $this;
    }

    /**
     * Binds a model instance to the form. If there is data, haste form will add
     * the present values as default values.
     *
     * @param \Model
     *
     * @return $this
     */
    public function bindModel(\Model $objModel = null)
    {
        $this->objModel = $objModel;

        return $this;
    }

    /**
     * Gets the bound model
     *
     * @return \Model
     */
    public function getBoundModel()
    {
        return $this->objModel;
    }

    /**
     * Add the Contao hidden fields FORM_SUBMIT and REQUEST_TOKEN
     */
    public function addContaoHiddenFields()
    {
        $this->addFormField('FORM_SUBMIT', array(
            'name' => 'FORM_SUBMIT',
            'inputType' => 'hidden',
            'ignoreModelValue' => true,
            'value' => $this->getFormId()
        ));

        $this->addFormField('REQUEST_TOKEN', array(
            'name' => 'REQUEST_TOKEN',
            'inputType' => 'hidden',
            'ignoreModelValue' => true,
            'value' => REQUEST_TOKEN
        ));
    }

    /**
     * Helper method to easily add a captcha field
     *
     * @param string        $strName The form field name
     * @param ArrayPosition $position
     */
    public function addCaptchaFormField($strName, ArrayPosition $position = null)
    {
        $this->addFormField($strName, array(
            'name'      => $strName . '_' . $this->strFormId, // make sure they're unique on a page
            'label'     => $GLOBALS['TL_LANG']['MSC']['securityQuestion'],
            'inputType' => 'captcha',
            'eval'      => array('mandatory'=>true)
        ), $position);
    }

    /**
     * Helper method to easily add a submit field
     *
     * @param string        $strName  The form field name
     * @param string        $strLabel The label for the submit button
     * @param ArrayPosition $position
     */
    public function addSubmitFormField($strName, $strLabel, ArrayPosition $position = null)
    {
        $this->addFormField($strName, array(
            'name'      => $strName,
            'label'     => $strLabel,
            'inputType' => 'submit'
        ), $position);
    }

    /**
     * Add form fields from a back end DCA
     *
     * @param string   $strTable    The DCA table name
     * @param callable $varCallback Called for each field, return true if you want to include the field in the form
     *
     * @return $this
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
     * Return true if the field has "inputType" set, false otherwise
     *
     * This is a default callback that can be used with addFieldsFromDca() method. It prevents from adding fields
     * that do not have inputType specified which would result in an exception. The fields you typically would
     * like to skip are: id, tstamp, pid, sorting.
     *
     * @param string $field
     * @param array  $dca
     *
     * @return bool
     */
    public function skipFieldsWithoutInputType($field, array $dca)
    {
        return isset($dca['inputType']);
    }

    /**
     * Add form fields from a back end form generator form ID
     *
     * @param int      $intId       The form generator form ID
     * @param callable $varCallback Called for each field, return true if you want to include the field in the form
     *
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function addFieldsFromFormGenerator($intId, $varCallback = null)
    {
        if (($objFields = \FormFieldModel::findPublishedByPid($intId)) === null) {
            throw new \InvalidArgumentException('Form ID "' . $intId . '" does not exist or has no published fields.');
        }

        while ($objFields->next()) {
            // make sure "name" is set because not all form fields do need it and it would thus overwrite the array indexes
            $strName = $objFields->name ?: 'field_' . $objFields->id;

            $this->checkFormFieldNameIsValid($strName);

            $arrDca = $objFields->row();

            // Make sure it has a "name" attribute because it is mandatory
            if (!isset($arrDca['name'])) {
                $arrDca['name'] = $strName;
            }

            if (is_callable($varCallback) && !call_user_func_array($varCallback, array(&$strName, &$arrDca))) {
                continue;
            }

            $this->arrFormFields[$strName] = $arrDca;
        }

        $this->intState = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Adds a form field from the form generator without trying to convert a DCA configuration.
     *
     * @param string             $strName
     * @param array              $arrDca
     * @param ArrayPosition|null $position
     */
    public function addFieldFromFormGenerator($strName, array $arrDca, ArrayPosition $position = null)
    {
        $this->checkFormFieldNameIsValid($strName);

        if (null === $position) {
            $position = ArrayPosition::last();
        }

        // make sure "name" is set because not all form fields do need it and it would thus overwrite the array indexes
        $strName = $arrDca['name'] ?: 'field_' . $arrDca['id'];

        // Make sure it has a "name" attribute because it is mandatory
        if (!isset($arrDca['name'])) {
            $arrDca['name'] = $strName;
        }

        $this->arrFormFields = $position->addToArray($this->arrFormFields, array($strName=>$arrDca));
        $this->intState = self::STATE_DIRTY;
    }

    /**
     * Get a form field by a given name
     *
     * @param string $strName The form field name
     *
     * @return array
     */
    public function getFormField($strName)
    {
        return $this->arrFormFields[$strName];
    }

    /**
     * Get all form fields
     *
     * @return array
     */
    public function getFormFields()
    {
        return $this->arrFormFields;
    }

    /**
     * Removes a form field
     *
     * @param string $strName The form field name
     *
     * @return $this
     */
    public function removeFormField($strName)
    {
        unset($this->arrFormFields[$strName]);
        $this->intState = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Checks if there is a form field with a given name
     *
     * @param string $strName The form field name
     *
     * @return bool
     */
    public function hasFormField($strName)
    {
        return isset($this->arrFormFields[$strName]);
    }

    /**
     * Returns a widget instance if existing
     *
     * @param string $strName The form field name
     *
     * @return \Widget
     */
    public function getWidget($strName)
    {
        $this->createWidgets();

        return $this->arrWidgets[$strName];
    }

    /**
     * Return all widgets
     *
     * @return array
     */
    public function getWidgets()
    {
        $this->createWidgets();

        return $this->arrWidgets;
    }

    /**
     * Add a validator to the form field
     *
     * @param string                      $strName      The form field name
     * @param ValidatorInterface|callable $varValidator An instance of ValidatorInterface or a callable that will be called on widget validation
     *
     * @return $this
     * @throws \InvalidArgumentException
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
     *
     * @return $this
     * @throws \RuntimeException
     */
    public function createWidgets()
    {
        // Do nothing if already generated
        if (!$this->isDirty()) {
            return $this;
        }

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
            if ('GET' === $this->getMethod()) {
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
     *
     * @return bool
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
                        $this->blnValid = false;
                    }
                }

                /** @noinspection NotOptimalIfConditionsInspection */
                if ($objWidget->hasErrors()) {
                    // Re-check the status in case a custom validator has added an error
                    $this->blnValid = false;

                } elseif ($this->objModel !== null) {
                    // Bind to Model instance
                    $this->objModel->$strName =  $varValue;
                }
            }
        }

        return $this->isValid();
    }

    /**
     * Add form to a template
     *
     * @param \FrontendTemplate $objTemplate
     *
     * @return $this
     */
    public function addToTemplate(\FrontendTemplate $objTemplate)
    {
        return $this->addToObject($objTemplate);
    }

    /**
     * Add form to a object
     *
     * @param object $objObject
     *
     * @return $this
     */
    public function addToObject($objObject)
    {
        $this->createWidgets();

        $objObject->action = $this->getFormAction();
        $objObject->formId = $this->getFormId();
        $objObject->method = strtolower($this->getMethod());
        $objObject->enctype = $this->getEnctype();
        $objObject->widgets = $this->arrWidgets;
        $objObject->valid = $this->isValid();
        $objObject->submitted = $this->isSubmitted();
        $objObject->hasUploads = $this->hasUploads();
        $objObject->novalidate = $this->generateNoValidate();
        $objObject->tableless = $this->blnTableless;

        /** @type \Widget $objWidget */
        $arrWidgets = $this->splitHiddenAndVisibleWidgets();

        // Generate hidden form fields
        foreach ((array) $arrWidgets['hidden'] as $objWidget) {
            $objObject->hidden .= $objWidget->parse();
        }

        // Generate visible form fields
        foreach ((array) $arrWidgets['visible'] as $objWidget) {
            $objObject->fields .= $objWidget->parse();
        }

        $objObject->hiddenWidgets  = $arrWidgets['hidden'];
        $objObject->visibleWidgets = $arrWidgets['visible'];

        $objObject->hasteFormInstance = $this;

        return $this;
    }

    /**
     * Get the helper object
     *
     * @return \stdClass
     */
    public function getHelperObject()
    {
        $helper = new \stdClass();
        $this->addToObject($helper);

        return $helper;
    }

    /**
     * Generate a form and return it as HTML string
     *
     * @param string|null $templateName The form wrapper template name or null to auto-select (based on Contao version).
     *
     * @return string
     */
    public function generate($templateName = null)
    {
        if (null === $templateName) {
            $templateName = 'form';

            try {
                \TemplateLoader::getPath($templateName, 'html5');
            } catch (\Exception $e) {
                $templateName = 'form_wrapper';
            }
        }

        $objTemplate = new \FrontendTemplate($templateName);
        $objTemplate->class = 'hasteform_' . $this->getFormId();
        $objTemplate->formSubmit = $this->getFormId();

        $this->addToTemplate($objTemplate);

        return $objTemplate->parse();
    }

    /**
     * Return the submitted data of a specific form field
     *
     * @param string $strName The form field name
     *
     * @return mixed    The value of the widget
     * @throws \BadMethodCallException
     * @throws \InvalidArgumentException
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
     *
     * @param callable $varCallback A callable that should be used to fetch the data instead of the built in functionality
     *
     * @return array
     */
    public function fetchAll($varCallback = null)
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
     *
     * @return string
     */
    public function __toString()
    {
        return $this->generate();
    }

    /**
     * Check for a valid form field name
     *
     * @param string $strName The form field name
     *
     * @throws \InvalidArgumentException
     */
    protected function checkFormFieldNameIsValid($strName)
    {
        if (is_numeric($strName)) {
            throw new \InvalidArgumentException('You cannot use a numeric form field name.');
        }

        if (in_array($strName, $this->arrFormFields, true)) {
            throw new \InvalidArgumentException(sprintf('"%s" has already been added to the form.', $strName));
        }
    }

    /**
     * Splits hidden and visible widgets
     *
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
