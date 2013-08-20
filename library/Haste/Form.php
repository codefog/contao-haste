<?php

namespace Haste;


class Form extends \Controller
{
    /**
     * HasteForm version
     */
    const VERSION = '2.0.0';

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
     * @param   string  The ID of the form
     * @param   string  The HTTP Method GET or POST
     * @param   Closure A closure that checks if the form has been submitted
     * @throws  InvalidArgumentException
     */
    public function __construct($strId, $strMethod, \Closure $objSubmitCheck)
    {
        if (is_numeric($strId)) {
            throw new \InvalidArgumentException('You cannot use a numeric form id.');
        }

        $this->strFormId = $strId;

        if (!in_array($strMethod, array('GET', 'POST'))) {
            throw new \InvalidArgumentException('The method has to be either GET or POST.');
        }

        $this->strMethod = $strMethod;
        $this->blnSubmitted = $objSubmitCheck($this);

        // The form action can be set using several helper methods but by default it's just
        // pointing to the current page
        $this->strFormAction = \Controller::generateFrontendUrl($GLOBALS['objPage']->row());
    }

    /**
     * Set the form action directly
     * @param   string  The URI
     */
    public function setFormActionFromUri($strUri)
    {
        $this->strFormAction = $strUri;
    }

    /**
     * Set the form action from a Contao page ID
     * @param   int  The page ID
     * @throws  InvalidArgumentException
     */
    public function setFormActionFromPageId($intId)
    {
        if (($objPage = \PageModel::findByPk($intId)) !== null) {
            $this->strFormAction = \Controller::generateFrontendUrl($objPage->row());
        }

        throw new \InvalidArgumentException(sprintf('The page id "%s" does apparently not exist!', $intId));
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
     * Check if the form has been submitted
     * @return  boolean
     */
    public function isSubmitted()
    {
        return $this->blnSubmitted;
    }

    /**
     * Adds a form field
     * @param   string  The form field name
     * @param   array   The DCA representation of the field
     */
    public function addFormField($strName, array $arrDca)
    {
        $this->checkFormFieldNameIsValid($strName);

        // Make sure it has a "name" attribute because it is mandatory
        if (!isset($arrDca['name'])) {
            $arrDca['name'] = $strName;
        }

        // Support the default value, too
        if (isset($arrDca['default']) && !isset($arrDca['value'])) {
            $arrDca['value'] = $arrDca['default'];
        }

        // Make the field tableless by default
        if (!isset($arrDca['eval']['tableless'])) {
            $arrDca['eval']['tableless'] = true;
        }

        $strClass = $GLOBALS['TL_FFL'][$arrDca['inputType']];

        if (!class_exists($strClass)) {
            throw new \RuntimeException(sprintf('The class "%s" for type "%s" could not be found.', $strClass, $arrDca['inputType']));
        }

        $arrDca = $strClass::getAttributesFromDca($arrDca, $arrDca['name'], $arrDca['value']);

        $this->arrFormFields[$strName] = $arrDca;
        $this->intState = self::STATE_DIRTY;
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
     * @param   string  The DCA table name
     * @param   Closure A closure that will be called on the array before adding (remove fields if you like)
     */
    public function addFieldsFromDca($strTable, \Closure $objCallback = null)
    {
        \System::loadLanguageFile($strTable);
        // Only because of that little call we have to extend Controller
        $this->loadDataContainer($strTable);

        $arrFields = $GLOBALS['TL_DCA'][$strTable]['fields'];
        if ($objCallback) {
            $objCallback($arrFields);
        }

        foreach ($arrFields as $k => $v) {
            $this->addFormField($k, $v);
        }
    }

    /**
     * Add form fields from a back end form generator form ID
     * @param   int     The form generator form ID
     * @param   Closure A closure that will be called on the array before adding (remove fields if you like)
     */
    public function addFieldsFromFormGenerator($intId, \Closure $objCallback = null)
    {
        if (($objFields = \FormFieldModel::findPublishedByPid($intId)) === null) {
            return;
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

            // Make the field tableless by default
            if (!isset($arrDca['eval']['tableless'])) {
                $arrDca['eval']['tableless'] = true;
            }

            $arrFields[$strName] = $arrDca;
        }

        if ($objCallback) {
            $objCallback($arrFields);
        }

        foreach ($arrFields as $k => $v) {
            $this->arrFormFields[$k] = $v;
        }

        $this->intState = self::STATE_DIRTY;
    }

    /**
     * Removes a form field
     * @param   string  The form field name
     */
    public function removeFormField($strName)
    {
        unset($this->arrFormFields[$strName]);
        $this->intState = self::STATE_DIRTY;
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
     * Create the widget instances
     * @throws RuntimeException
     */
    public function createWidgets()
    {
        // Do nothing if already generated
        if (!empty($this->arrWidgets) && $this->intState === self::STATE_CLEAN) {
            return;
        }

        // Initialize widgets
        foreach ($this->arrFormFields as $strName => $arrField) {

            $strClass = $GLOBALS['TL_FFL'][$arrField['type']];

            if (!class_exists($strClass)) {
                throw new \RuntimeException(sprintf('The class "%s" for type "%s" could not be found.', $strClass, $arrField['type']));
            }

            $objWidget = new $strClass($arrField);

            if ($objWidget instanceof \uploadable) {
                $this->blnHasUploads = true;
            }

            $this->arrWidgets[$strName] = $objWidget;
        }

        if ($this->strMethod == 'GET' && $this->blnHasUploads) {
            throw new \RuntimeException('How do you want me to upload your file using GET?');
        }

        if ($this->blnHasUploads) {
            $this->strEnctype = 'multipart/form-data';
        } else {
            $this->strEnctype = 'application/x-www-form-urlencoded';
        }

        $this->intState == self::STATE_CLEAN;
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
            $varValue = $objWidget->value;

            // Save callback
            if (is_array($this->arrFields[$strName]['save_callback'])) {
                foreach ($this->arrFields[$strName]['save_callback'] as $callback) {
                    $objCallback = System::importStatic($callback[0]);

                    try {
                        $varValue = $objCallback->$callback[1]($varValue, $this);
                    }
                    catch (\Exception $e) {
                        $objWidget->class = 'error';
                        $objWidget->addError($e->getMessage());
                    }
                }
            }

            if ($objWidget->hasErrors()) {
                $this->blnValid = false;
            }

            $objWidget->value = $varValue;
        }

        return $this->blnValid;
    }

    /**
     * Add form to a template
     * @param   FrontendTemplate
     */
    public function addToTemplate(\FrontendTemplate $objTemplate)
    {
        $this->createWidgets();

        $objTemplate->action = $this->strFormAction;
        $objTemplate->formId = $this->strFormId;
        $objTemplate->method = $this->strMethod;
        $objTemplate->enctype = $this->strEnctype;
        $objTemplate->fields = $this->arrWidgets;
        $objTemplate->valid = $this->blnValid;
        $objTemplate->submitted = $this->blnSubmitted;
        $objTemplate->hasUploads = $this->blnHasUploads;

        $objTemplate->hasteFormInstance = $this;
    }

    /**
     * Generate a form and return it as HTML string
     * @return string
     */
    public function generateAsString()
    {
        $this->createWidgets();

        $objTemplate = new \FrontendTemplate('form');
        $objTemplate->class = 'hasteform_' . $this->strFormId;
        $objTemplate->tableless = true;
        $objTemplate->action = $this->strFormAction;
        $objTemplate->formId = $this->strFormId;
        $objTemplate->method = strtolower($this->strMethod);
        $objTemplate->enctype = $this->strEnctype;
        $objTemplate->formSubmit = $this->strFormId;

        // Generate all form fields
        foreach ($this->arrWidgets as $objWidget) {
            $objTemplate->fields .= '<div class="widget ' . $objWidget->name . '">' . $objWidget->parse() . '</div>';
        }

        return $objTemplate->parse();
    }

    /**
     * Generate a form and return it as HTML string
     * @return string
     */
    public function __toString()
    {
        return $this->generateAsString();
    }

    /**
     * Check for a valid form field name
     * @param   string  The form field name
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
}