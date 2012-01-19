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
 * @copyright  Kamil Kuzminski 2011 
 * @author     Kamil Kuzminski <http://qzminski.com> 
 * @package    Haste 
 * @license    LGPL
 */


/**
 * Class HasteForm 
 *
 * @copyright  Kamil Kuzminski 2011 
 * @author     Kamil Kuzminski <http://qzminski.com> 
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
	 * Configuratoin
	 * @var array
	 */
	protected $arrConfiguration = array();


	/**
	 * Initialize the object
	 * @param string
	 * @param array
	 */
	public function __construct($strId, $arrFields)
	{
		parent::__construct();

		global $objPage;
		$this->strFormId = 'form_' . $strId;
		$this->arrFields = $arrFields;

		$this->arrConfiguration['method'] = 'post';
		$this->arrConfiguration['action'] = ampersand($this->getIndexFreeRequest());
		$this->arrConfiguration['submit'] = $GLOBALS['TL_LANG']['MSC']['submit'];
		$this->arrConfiguration['javascript'] = true;
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

			default:
				return $this->arrConfiguration[$strKey];
				break;
		}
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
		foreach ($this->arrFields as $arrField)
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
			$objWidget = new $strClass($this->prepareForWidget($arrField, $arrField['name'], $arrField['value']));

			// Set current widget value if this is a GET request
			if ($this->arrConfiguration['method'] == 'get')
			{
				$objWidget->value = $this->Input->get($arrField['name']);
			}

			$this->arrWidgets[$arrField['name']] = $objWidget;
		}
	}


	/**
	 * Validate the form
	 * @return boolean
	 */
	public function validate()
	{
		$this->initializeWidgets();
		$blnIsGet = ($this->arrConfiguration['method'] == 'get' && count($_GET) > 0) ? true : false;

		if ($blnIsGet || $this->Input->post('FORM_SUBMIT') == $this->strFormId)
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
			$strBuffer .= '
<div class="widget">' .
$objWidget->generateLabel() . ' ' . $objWidget->generateWithError() .
'</div>';
		}

		$strBuffer .= '
<div class="submit_container">
<input type="submit" class="submit" value="' . $this->arrConfiguration['submit'] . '"' . $tagEnding . '
</div>
</div>
</form>';

		// Add a javascript if there is an error
		if (!$this->blnValid && $this->arrConfiguration['javascript'])
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
}

?>