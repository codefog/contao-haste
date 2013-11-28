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
     * Get the form action
     * @param   string  The URI
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
        return $this->blnSubmitted;
    }

    /**
     * Check if there are uploads
     * @return  boolean
     */
    public function hasUploads()
    {
        // We need to create the widgets to know if we have uploads
        $this->createWidgets();

        return $this->blnHasUploads;
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

        // Some widgets render the mandatory asterisk only based on "require" attribute
        if (!isset($arrDca['required'])) {
            $arrDca['eval']['required'] = (bool) $arrDca['eval']['mandatory'];
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

        if (is_array($arrDca['save_callback'])) {
            $arrCallbacks = $arrDca['save_callback'];
            $this->addValidator($strName, function($objWidget) use ($arrCallbacks) {
                foreach ($arrCallbacks as $callback) {
                    try {
                        if (is_array($callback)) {
                            $objCallback = System::importStatic($callback[0]);
                            $objWidget->value = $objCallback->$callback[1]($objWidget->value, $this);
                        } elseif (is_callable($callback)) {
                            $objWidget->value = $callback($objWidget->value, $this);
                        }
                    } catch (\Exception $e) {
                        $objWidget->class = 'error';
                        $objWidget->addError($e->getMessage());
                    }
                }
            });
        }

        // Convert date formats into timestamps
        if ($arrDca['eval']['rgxp'] == 'date' || $arrDca['eval']['rgxp'] == 'time' || $arrDca['eval']['rgxp'] == 'date') {
            $this->addValidator($strName, function($objWidget) use ($arrDca) {
                if ($objWidget->value != '') {
                	$objDate = new \Date($objWidget->value, $GLOBALS['TL_CONFIG'][$arrDca['eval']['rgxp'] . 'Format']);
                	$objWidget->value = $objDate->tstamp;
                }
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
    public function bindModel(\Model $objModel)
    {
        $this->objModel = $objModel;
    }

    /**
     * Gets the bound model
     * @return   \Model
     * @throws   \BadMethodCallException
     */
    public function getBoundModel()
    {
        if ($this->objModel === null) {
            throw new \BadMethodCallException('There was no bound instance of "Model" found!');
        }

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
     * @param   callable A callable that will be called on the array before adding (remove fields if you like)
     * @return  Form
     */
    public function addFieldsFromDca($strTable, $varCallback = null)
    {
        \System::loadLanguageFile($strTable);
        $this->loadDataContainer($strTable);
        $arrFields = $GLOBALS['TL_DCA'][$strTable]['fields'];

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
     * @param   callable A callable that will be called on the array before adding (remove fields if you like)
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
        return $this->arrWidgets[$strName];
    }

    /**
     * Add a validator to the form field
     * @param   string   The form field name
     * @param   callable A callable that will be called on widget validation
     * @return  Form
     */
    public function addValidator($strName, $varCallback)
    {
        if (is_callable($varCallback)) {
            $this->arrValidators[$strName][] = $varCallback;
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
        if (!empty($this->arrWidgets) && $this->intState === self::STATE_CLEAN) {
            return;
        }

        $intTotal = count($this->arrFormFields);
        $i = 0;

        // Initialize widgets
        foreach ($this->arrFormFields as $strName => $arrField) {

            $strClass = $GLOBALS['TL_FFL'][$arrField['type']];

            if (!class_exists($strClass)) {
                throw new \RuntimeException(sprintf('The class "%s" for type "%s" could not be found.', $strClass, $arrField['type']));
            }

            $arrField['tableless']  = $this->blnTableless;
            $arrField['rowClass']   = $this->generateRowClass($i, $intTotal);

            $objWidget = new $strClass($arrField);

            if ($objWidget instanceof \uploadable) {
                $this->blnHasUploads = true;
            }

            $this->arrWidgets[$strName] = $objWidget;
            $i++;
        }

        if ($this->strMethod == 'GET' && $this->blnHasUploads) {
            throw new \RuntimeException('How do you want me to upload your file using GET?');
        }

        if ($this->blnHasUploads) {
            $this->strEnctype = 'multipart/form-data';
        } else {
            $this->strEnctype = 'application/x-www-form-urlencoded';
        }

        $this->intState = self::STATE_CLEAN;

        return $this;
    }

    /**
     * Validate the form
     * @return boolean
     */
    public function validate()
    {
        $this->createWidgets();

        if (!$this->blnSubmitted) {
            return false;
        }

        foreach ($this->arrWidgets as $strName => $objWidget) {
            $objWidget->validate();

            // Run custom validators
            if (isset($this->arrValidators[$strName])) {
                foreach ($this->arrValidators[$strName] as $varCallback) {
                    call_user_func($varCallback, $objWidget);
                }
            }

            if ($objWidget->hasErrors()) {
                $this->blnValid = false;
            }
            elseif ($objWidget->submitInput()) {
                // Bind to Model instance
                if ($this->objModel !== null) {
                    $this->objModel->$strName =  $objWidget->value;
                }
            }
        }

        return $this->blnValid;
    }

    /**
     * Add form to a template
     * @param   FrontendTemplate
     * @return  Form
     */
    public function addToTemplate(\FrontendTemplate $objTemplate)
    {
        $this->createWidgets();

        $objTemplate->action = $this->strFormAction;
        $objTemplate->formId = $this->strFormId;
        $objTemplate->method = $this->strMethod;
        $objTemplate->enctype = $this->strEnctype;
        $objTemplate->widgets = $this->arrWidgets;
        $objTemplate->valid = $this->blnValid;
        $objTemplate->submitted = $this->blnSubmitted;
        $objTemplate->hasUploads = $this->blnHasUploads;
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
        $objTemplate->class = 'hasteform_' . $this->strFormId;
        $objTemplate->tableless = $this->blnTableless;
        $objTemplate->action = $this->strFormAction;
        $objTemplate->formId = $this->strFormId;
        $objTemplate->method = strtolower($this->strMethod);
        $objTemplate->enctype = $this->strEnctype;
        $objTemplate->formSubmit = $this->strFormId;

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
        if (!$this->blnSubmitted) {
            throw new \BadMethodCallException('How do you want me to fetch data from an unsubmitted form?');
        }

        if ($this->strMethod !== 'POST') {
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
     * Generates a the row CSS class for the widget
     * @param   int Current index
     * @param   int Total number of widgets
     */
    protected function generateRowClass($intIndex, $intTotal)
    {
        return 'row_' . $intIndex . (($intIndex == 0) ? ' row_first' : (($intIndex == ($intTotal - 1)) ? ' row_last' : '')) . ((($intIndex % 2) == 0) ? ' even' : ' odd');
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