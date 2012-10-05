HasteForm
======================

About
-----

HasteForm has been designed to ease working with Contao forms in the front end.


Contributors
-------------------

* Kamil Kuzminski <kamil.kuzminski@gmail.com>
* Yanick Witschi <yanick.witschi@terminal42.ch>
* Andreas Schempp <andreas.schempp@terminal42.ch>


Examples
------------

A lot of the following examples can be combined.
For more internal details please read the source ;-)

### Simple form
```
<?php

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

	// The submit button
	$objForm->submit = 'Submit form';

	// validate() also checks whether the form has been submitted
	if ($objForm->validate())
	{
		// fetch all form data
		$arrData = $objForm->fetchAll();

		// fetch the value of one specific field
		$varValue = $objForm->fetch('year');
	}

	// get the form as string
	echo $objForm->generateForm();
```

### HasteForm supports GET and POST

```
<?php

	$objForm->method = 'get'; // 'post' is default
```

### Using a custom form template

```
<?php
	// you can also work with a custom template if you don't like getting a string directly using generateForm()
	// this passes the data to a custom FrontendTemplate instance where you have to make sure that fieldsets etc. are respected
	$objForm->addFormToTemplate($objTemplate);
```

### Adding a default captcha

```
<?php
	// easily add a captcha if you like
	$objForm->addCaptcha();
```

### Working with fieldsets

```
<?php
	// you can start a new fieldset for every field. It will contain all the following widgets until the next field you call
	// addFieldSet() upon or all widgets if you call it only for one field
	$objForm->addFieldSet('year');
```

### Load the form fields from a back end DCA

```
<?php
	// you can exclude certain fields by passing an array of field names as second parameter
	$objForm->loadFieldsFromDca('tl_content', $arrFieldsIdontNeed);
```

### Load the form fields from a form generator id

```
<?php
	// this is really cool - simply load all the settings from a form generated with the form generator of Contao
	$objForm->loadFieldsFromFormGenerator($intId, $arrFieldsIdontNeed);
```

### Adding and removing fields on a HasteForm instance

```
<?php
	// you can add fields later at the very end or before any widget
	// simply pass the field name, an array containing the configuration and optionally the field name of the widget you want to add your new widget in front of
	$objForm->addField('firstname',	array
								(
									'label'     => 'First name',
									// etc.
								), 'beforeFieldName');

	// and obviously remove a specific field
	$objForm->removeField('firstname');
```

### More cool stuff

```
<?php
	// setting an action
	$objForm->action = 12; // default is the same page. You can pass a string or an id which HasteForm will try to convert into a front end url

	// getting the fields
	$arrFields = $objForm->fields;

	// getting the widgets
	$arrWidgets = $objForm->widgets;

	// most of the times validate() is enough but sometimes you might need to separately know whether the form has been submitted without validating
	$blnSubmitted = $objForm->isSubmitted;

```