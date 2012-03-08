HasteForm
======================

About
-----

HasteForm has been designed to ease working with Contao forms in the front end.


Contributors
-------------------

* Kamil Kuzminski <kamil.kuzminski@gmail.com>
* Yanick Witschi <yanick.witschi@certo-net.ch>


Example
------------
	$arrFields = array();
	$arrFields['year'] = array
	(
		'name'			=> 'year',
		'label'			=> 'Year',
		'inputType'		=> 'text',
		'eval'			=> array('mandatory'=>true, 'rgxp'=>'digit')
	);
	
	// first param is the form id
	$objForm = new HasteForm('someid', $arrFields);
	$objForm->submit = 'Submit form';
	
	// easily add a captcha if you like
	$objForm->addCaptcha();
	
	// you can start a new fieldset for every field. It will contain all widgets until the next field you call
	// addFieldSet() upon or all widgets, if you call it only for one field
	$objForm->addFieldSet('year');
	
	// validate() also checks whether the form has been submitted
	if ($objForm->validate())
	{
		// fetch all form data
		$arrData = $objForm->fetchAll();
		
		// fetch the value of one specific field
		$varValue = $objForm->fetch('year');
	}
	
	// You have two ways to output the form
	
	// #1: directly get the string
	$objForm->generateForm();
	
	// #2: pass the data to a custom FrontendTemplate instance where you have to make sure that fieldsets etc. are respected yourself
	$objForm->addFormToTemplate($objTemplate);
