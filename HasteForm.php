<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2011 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Kamil Kuzminski 2011-2012
 * @author     Kamil Kuzminski <kamil.kuzminski@gmail.com> 
 * @package    Haste 
 * @license    LGPL
 */


/**
 * Class HasteForm 
 *
 * @copyright  Kamil Kuzminski 2011-2012
 * @author     Kamil Kuzminski <kamil.kuzminski@gmail.com> 
 * @author     Yanick Witschi <yanick.witschi@certo-net.ch>
 * @package    Haste
 */
class HasteForm extends Frontend
{

	/**
	 * Form ID
	 * @var string
	 */
	protected $strFormId;

	/**
	 * Validation status
	 * @var boolean
	 */
	protected $blnValid = false;

	/**
	 * Form submitted
	 * @var boolean
	 */
	protected $blnSubmitted = false;

	/**
	 * Fields
	 * @var array
	 */
	protected $arrFields = array();

	/**
	 * Widgets
	 * @var array
	 */
	protected $arrWidgets = array();

	/**
	 * Configuration
	 * @var array
	 */
	protected $arrConfiguration = array();

	/**
	 * Fieldsets
	 * @var array
	 */
	protected $arrFieldsets = array();

	/**
	 * Has fieldsets
	 * @var boolean
	 */
	protected $blnHasFieldsets = false;


	/**
	 * Initialize the object
	 * @param string
	 * @param array fields (optional, you can also set them from a DCA)
	 */
	public function __construct($strId, $arrFields=array())
	{
		parent::__construct();

		global $objPage;
		$this->strFormId = 'form_' . $strId;
		$this->arrFields = $arrFields;

		$this->arrConfiguration['method'] = 'post';
		$this->arrConfiguration['action'] = ampersand($this->getIndexFreeRequest());
		$this->arrConfiguration['submit'] = $GLOBALS['TL_LANG']['MSC']['submit'];
		$this->arrConfiguration['javascript'] = true;
		
		// check if the form has been submitted
		$blnIsGet = ($this->arrConfiguration['method'] == 'get' && count($_GET) > 0) ? true : false;
		$this->blnSubmitted = ($blnIsGet || $this->Input->post('FORM_SUBMIT') == $this->strFormId);
	}


	/**
	 * Set an object property
	 * @param string
	 * @param mixed
	 * @throws Exception
	 */
	public function __set($strKey, $varValue)
	{
		// Validate the form method
		switch ($strKey)
		{
			case 'method':
				$varValue = strtolower($varValue);

				if (!in_array($varValue, array('get', 'post')))
				{
					throw new Exception(sprintf('Invalid form method "%s"!', $varValue));
				}

				// Remove _GET parameters
				if ($varValue == 'get')
				{
					$this->arrConfiguration['action'] = $this->removeGetParameters($this->arrConfiguration['action']);
				}
				break;

			case 'action':
				if (!$varValue)
				{
					return;
				}

				// Generate a frontend URL
				if (is_numeric($varValue))
				{
					$objRedirectPage = $this->Database->prepare("SELECT id, alias FROM tl_page WHERE id=?")
													  ->limit(1)
													  ->execute($varValue);

					if ($objRedirectPage->numRows)
					{
						$varValue = $this->generateFrontendUrl($objRedirectPage->row());
					}
					else
					{
						global $objPage;
						$varValue = $this->generateFrontendUrl($objPage->row());
					}
				}

				// Remove _GET parameters
				if ($this->arrConfiguration['method'] == 'get')
				{
					$varValue = $this->removeGetParameters($varValue);
				}

				$varValue = ampersand($varValue);
				break;

			case 'submit':
				$varValue = specialchars($varValue);
				break;
		}

		$this->arrConfiguration[$strKey] = $varValue;
	}


	/**
	 * Return an object property
	 * @param string
	 * @return mixed
	 */
	public function __get($strKey)
	{
		switch ($strKey)
		{
			case 'formId':
				return $this->strFormId;
				break;

			case 'fields':
				return $this->arrFields;
				break;

			case 'widgets':
				return $this->arrWidgets;
				break;

			case 'enctype':
				return $this->arrConfiguration['hasUploads'] ? 'multipart/form-data' : 'application/x-www-form-urlencoded';
				break;

			case 'isSubmitted':
				return $this->blnSubmitted;
				break;

			default:
				return $this->arrConfiguration[$strKey];
				break;
		}
	}


	/**
	 * Load the fields from a back end DCA
	 * @param string the DCA table name
	 * @param array an array of fields you want to skip (optional)
	 */
	public function loadFieldsFromDca($strTable, $arrExclulde=array())
	{
		$this->loadDataContainer($strTable);
		$this->loadLanguageFile($strTable);
		
		foreach ($GLOBALS['TL_DCA'][$strTable]['fields'] as $strFieldName => $arrFieldData)
		{
			if (in_array($strFieldName, $arrExclulde))
			{
				continue;
			}
			
			$this->addField($strFieldName, $arrFieldData);
		}
	}


	/**
	 * Add a field to the fields array (optionally after a defined one)
	 * @param string the field name
	 * @param array the field data
	 * @param string a field name (if the current should be injected before a reference field)
	 */
	public function addField($strFieldName, $arrFieldData, $strInjectBefore=false)
	{
		if ($strInjectBefore)
		{
			$arrKeys = array_keys($this->arrFields);
			$intIndex = array_search($strInjectBefore, $arrKeys);
			
			if (!$intIndex)
			{
				throw new Exception('The field "' . $strInjectBefore . '" is not yet defined!');
			}
			
			$arrNew = array();
			$arrNew[$strFieldName] = $arrFieldData; 

			array_insert($this->arrFields, $intIndex, $arrNew);
			return;
		}

		$this->arrFields[$strFieldName] = $arrFieldData;
	}
	
	
	/**
	 * Remove a field
	 * @param string field name
	 */
	public function removeField($strFieldName)
	{
		unset($this->arrFields[$strFieldName]);
	}
	

	/**
	 * Start a new fieldset group after a given fieldname
	 * It will include either all widgets if only applied once or all widgets until the field where you call this method again
	 * @param string widget field name
	 * @throws Exception
	 */
	public function addFieldSet($strField)
	{
		if (in_array($strField, $this->arrFieldsets))
		{
			throw new Exception(sprintf('There already exists a fieldset starting at the field "%s"!', $strField));
		}

		$this->blnHasFieldsets = true;
		$this->arrFieldsets[] = $strField;
	}


	/**
	 * Initialize the form
	 * @param boolean
	 */
	public function initializeWidgets($blnForce=false)
	{
		// Return if the widgets have been already initialized
		if (count($this->arrWidgets) > 0 && !$blnForce)
		{
			return;
		}

		// Initialize widgets
		foreach ($this->arrFields as $strFieldName => $arrField)
		{
			$strClass = $GLOBALS['TL_FFL'][$arrField['inputType']];

			// Continue if the class is not defined
			if (!$this->classFileExists($strClass))
			{
				continue;
			}

			// Update the configuration if a form has upload fields
			if ($strClass == 'FormFileUpload')
			{
				$this->arrConfiguration['hasUploads'] = true;
			}

			$arrField['eval']['required'] = $arrField['eval']['mandatory'];

			// support the default value too
			$arrField['value'] = $arrField['default'];
			
			// make sure it has a "name" attribute because it is mandatory
			if (!isset($arrField['name']))
			{
				$arrField['name'] = $strFieldName;
			}
			
			$objWidget = new $strClass($this->prepareForWidget($arrField, $arrField['name'], $arrField['value']));

			// Set current widget value if this is a GET request
			if ($this->arrConfiguration['method'] == 'get')
			{
				$objWidget->value = $this->Input->get($arrField['name']);
			}

			$this->arrWidgets[$arrField['name']] = $objWidget;
		}
		
		$this->prepareFieldSets();
	}


	/**
	 * Validate the form
	 * @return boolean
	 */
	public function validate()
	{
		$this->initializeWidgets();

		if ($this->blnSubmitted)
		{
			$this->blnValid = true;

			// Perform validation even on GET request
			if ($blnIsGet)
			{
				$arrPost = $_POST;
				$_POST = array_merge($_POST, $_GET);
			}

			// Validate widgets
			foreach ($this->arrWidgets as $objWidget)
			{
				$this->customValidation($objWidget);
				$objWidget->validate();

				if ($objWidget->hasErrors())
				{
					$this->blnValid = false;
				}

				// Check if form was submitted
				if ($blnIsGet && !isset($_GET[$objWidget->name]))
				{
					$this->blnValid = false;
				}
			}

			// Revert $_POST to its original form
			if ($blnIsGet)
			{
				$_POST = $arrPost;
			}
		}

		return $this->blnValid;
	}


	/**
	 * Perform a custom validation
	 * 
	 * Currently available:
	 * - mandatoryOn => array($field => $value);
	 *   Performs a mandatory check if $field is set to $value.
	 * - compare => array($field => 'comparison')
	 *   Check if value of the $field1 is lower/equal/higher than value of $field2.
	 *   Available comparisons: !=, ==, >, >=, <, <=
	 * @param object
	 * @throws Exception
	 */
	public function customValidation(Widget &$objWidget)
	{
		// Check if the field is mandatory depending on the value of other field
		if (is_array($objWidget->mandatoryOn) && count($objWidget->mandatoryOn) && array_is_assoc($objWidget->mandatoryOn))
		{
			foreach ($objWidget->mandatoryOn as $field => $value)
			{
				if ($this->arrWidgets[$field]->value == $value)
				{
					$objWidget->mandatory = true;
				}
			}
		}

		// Check values of the two fields
		if (is_numeric($objWidget->value) && is_array($objWidget->compare) && count($objWidget->compare) && array_is_assoc($objWidget->compare))
		{
			foreach ($objWidget->compare as $field => $comparison)
			{
				if (($this->arrWidgets[$field]->value != '') && !is_numeric($this->arrWidgets[$field]->value))
				{
					throw new Exception(sprintf('Field "%s" must have a numeric value!', $this->arrWidgets[$field]->label));
				}

				switch ($comparison)
				{
					case '!=':
						if ($objWidget->value == $this->arrWidgets[$field]->value)
						{
							$objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['equal'], $objWidget->label, $this->arrWidgets[$field]->label));
						}
						break;

					case '==':
						if ($objWidget->value != $this->arrWidgets[$field]->value)
						{
							$objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['notEqual'], $objWidget->label, $this->arrWidgets[$field]->label));
						}
						break;

					case '>':
						if ($objWidget->value <= $this->arrWidgets[$field]->value)
						{
							$objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['notGreater'], $objWidget->label, $this->arrWidgets[$field]->label));
						}
						break;

					case '>=':
						if ($objWidget->value < $this->arrWidgets[$field]->value)
						{
							$objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['notGreaterOrEqual'], $objWidget->label, $this->arrWidgets[$field]->label));
						}
						break;

					case '<':
						if ($objWidget->value >= $this->arrWidgets[$field]->value)
						{
							$objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['notLess'], $objWidget->label, $this->arrWidgets[$field]->label));
						}
						break;

					case '<=':
						if ($objWidget->value > $this->arrWidgets[$field]->value)
						{
							$objWidget->addError(sprintf($GLOBALS['TL_LANG']['ERR']['notLessOrEqual'], $objWidget->label, $this->arrWidgets[$field]->label));
						}
						break;
				}
			}
		}
	}


	/**
	 * Get a prticular widget value
	 * @param string
	 * @return mixed
	 */
	public function fetch($strWidget)
	{
		if (array_key_exists($strWidget, $this->arrWidgets))
		{
			return ($this->arrConfiguration['method'] == 'post') ? $this->arrWidgets[$strWidget]->value : $this->Input->get($strWidget);
		}

		return null;
	}


	/**
	 * Get value from all widgets	 
	 */
	public function fetchAll()
	{
		$arrData = array();

		foreach (array_keys($this->arrWidgets) as $widget)
		{
			$arrData[$widget] = $this->fetch($widget);
		}

		return $arrData;
	}


	/**
	 * Add a captcha field
	 */
	public function addCaptcha()
	{
		if (!isset($this->arrFields['captcha']))
		{
			$this->arrFields['captcha'] = array
			(
				'name'      => 'captcha',
				'label'     => $GLOBALS['TL_LANG']['MSC']['securityQuestion'],
				'inputType' => 'captcha',
				'eval'      => array('mandatory'=>true)
			);
		}
	}


	/**
	 * Add form to a template
	 * @param object
	 */
	public function addFormToTemplate($objTemplate)
	{
		$this->initializeWidgets();
		
		$objTemplate->formId = $this->strFormId;
		$objTemplate->method = $this->arrConfiguration['method'];
		$objTemplate->action = $this->arrConfiguration['action'];
		$objTemplate->enctype = $this->arrConfiguration['hasUploads'] ? 'multipart/form-data' : 'application/x-www-form-urlencoded';
		$objTemplate->submit = $this->arrConfiguration['submit'];
		$objTemplate->fields = $this->arrWidgets;
		$objTemplate->hasError = !$this->blnValid;
	}


	/**
	 * Generate a form and return it as HTML string
	 * @return string
	 */
	public function generateForm()
	{
		$this->initializeWidgets();
		global $objPage;
		list($tagEnding, $tagScriptStart, $tagScriptEnd) = ($objPage->outputFormat == 'html5') ? array('>', '<script>', '</script>') : array(' />', ('<script type="text/javascript">'."\n".'<!--//--><![CDATA[//><!--'), ('//--><!]]>'."\n".'</script>'));

		$strBuffer .= '
<form action="' . $this->arrConfiguration['action'] . '" id="' . $this->strFormId . '" enctype="' . ($this->arrConfiguration['hasUploads'] ? 'multipart/form-data' : 'application/x-www-form-urlencoded') . '" method="' . $this->arrConfiguration['method'] . '">
<div class="formbody">';

		// Generate required hidden inputs
		if ($this->arrConfiguration['method'] == 'post')
		{
			$strBuffer .= '
<input type="hidden" name="FORM_SUBMIT" value="' . $this->strFormId . '"' . $tagEnding . '
<input type="hidden" name="REQUEST_TOKEN" value="' . REQUEST_TOKEN . '"' . $tagEnding;
		}

		// Generate all fields
		foreach ($this->arrWidgets as $objWidget)
		{
			// start fieldset if we should do that for this widget
			if ($objWidget->hasteFormFieldSetStart)
			{
				$strBuffer .= sprintf('<fieldset class="%s">', $objWidget->hasteFormFieldCSSClass);
			}

			$strBuffer .= '<div class="widget">' . $objWidget->parse() . '</div>';
		
			// end fieldset if we should do that for this widget
			if ($objWidget->hasteFormFieldSetEnd)
			{
				$strBuffer .= '</fieldset>';
			}
		}

		$strBuffer .= '
<div class="submit_container">
<input type="submit" class="submit" value="' . $this->arrConfiguration['submit'] . '"' . $tagEnding . '
</div>
</div>
</form>';

		// Add a javascript if there is an error
		if ($this->blnSubmitted && !$this->blnValid && $this->arrConfiguration['javascript'])
		{
			$strBuffer .= '
' . $tagScriptStart . '
<!--//--><![CDATA[//><!--
window.scrollTo(null, ($(\''. $this->strFormId . '\').getElement(\'p.error\').getPosition().y - 20));
-->' . $tagScriptEnd;
		}

		return $strBuffer;
	}


	/**
	 * Remove _GET parameters from the URL
	 * @param string
	 * @return string
	 */
	protected function removeGetParameters($strUrl)
	{
		if ($GLOBALS['TL_CONFIG']['disableAlias'])
		{
			return $strUrl;
		}

		// Strip GET params
		if (($index = strpos($strUrl, '?')) !== false)
		{
			return substr($strUrl, 0, strpos($strUrl, '?'));
		}

		return $strUrl;
	}
	
	
	/**
	 * Prepare the fieldsets
	 */
	protected function prepareFieldSets()
	{
		if (!$this->blnHasFieldsets)
		{
			return;
		}
		
		$intTotal = count($this->arrWidgets);
		$i=0;
		$strPrevious = '';

		// Add hasteform specific properties("hasteFormFieldSetStart", "hasteFormFieldSetEnd") to every widget
		foreach ($this->arrWidgets as $objWidget)
		{
			if (in_array($objWidget->name, $this->arrFieldsets))
			{
				// if we have already added a fieldset to any widget, the previous needs to be closed
				if ($strPrevious)
				{
					$this->arrWidgets[$strPrevious]->hasteFormFieldSetEnd = true;
				}
				
				$objWidget->hasteFormFieldSetStart = true;
				$objWidget->hasteFormFieldCSSClass = 'fs_' . array_search($objWidget->name, $this->arrFieldsets);
			}
			
			// Close the last fieldset
			if ($i == ($intTotal-1))
			{
				$objWidget->hasteFormFieldSetEnd = true;
			}

			$strPrevious = $objWidget->name;
			$i++;
		}
	}
}