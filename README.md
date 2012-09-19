HasteForm
======================

About
-----

HasteForm has been designed to ease working with Contao forms in the front end.


Contributors
-------------------

* Kamil Kuzminski <kamil.kuzminski@gmail.com>
* Yanick Witschi <yanick.witschi@terminal42.ch>


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
	
	// the checkbox is really annoying. It took me a few minutes to get what I wanted so I leave it here for the record
	$arrFields['termsOfUse'] = array
	(
		'name'			=> 'termsOfUse',
		'label'			=> array('This is the <legend>', 'This is the <label>'),
		'inputType'		=> 'checkbox',
		'eval'			=> array('mandatory'=>true)
	);
	
	// first param is the form id
	$objForm = new HasteForm('someid', $arrFields);
	
	// by the way: you can also load the fields from a DCA file instead of passing $arrFields to the constructor
	$objForm->loadFieldsFromDca('tl_content', $arrFieldsIdontNeed=array());
	
	// The submit button
	$objForm->submit = 'Submit form';
	
	// easily add a captcha if you like
	$objForm->addCaptcha();
	
	// you can start a new fieldset for every field. It will contain all widgets until the next field you call
	// addFieldSet() upon or all widgets, if you call it only for one field
	$objForm->addFieldSet('year');
	
	// you can also add fields later on at the very end or before a certain widget
	$objForm->addField('type',	array
								(
									'label'     => 'Typ',
									// etc.
								), 'beforeFieldName');
	
	// and obviously remove a specific field
	$objForm->removeField('type');
	
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
