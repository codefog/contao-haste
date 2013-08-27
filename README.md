# HasteForm [![Build Status](https://travis-ci.org/codefog/contao-haste.png)](https://travis-ci.org/codefog/contao-haste)

About
-----

HasteForm has been designed to ease working with Contao forms in the front end.

This extension is compatible with Contao 3.1+ only.


Contributors
-------------------

* Kamil Kuzminski <kamil.kuzminski@codefog.pl>
* Yanick Witschi <yanick.witschi@terminal42.ch>
* Andreas Schempp <andreas.schempp@terminal42.ch>

Dependencies
-------------------

* NamespaceClassLoader: https://github.com/terminal42/contao-NamespaceClassLoader


Examples
------------

A lot of the following examples can be combined.
For more internal details please read the source ;-)

### Preparing a form instance
```php
<?php

    // First param is the form id
    // Second is either GET or POST
    // Third is a callable that decides when your form is submitted
    $objForm = new \Haste\Form('someid', 'POST', function($haste) {
        return \Input::post('FORM_SUBMIT') === $haste->getFormId();
    });

    // Haste will never decide for you when the form has been submitted.
    // You have to tell it! Let's have a look at an example using GET
    // Haste will turn into the submitted state as soon as the GET param
    // "foo" contains the value "bar"
    $objForm = new \Haste\Form('someid', 'GET', function() {
        return \Input::get('foo') === 'bar';
    });

    // A form needs an action. By default it's the current Contao page you
    // place your Haste form on, but you can either set your own URI:
    $objForm->setFormActionFromUri('https://foo.bar/somewhere.html');

    // Or you can pass a page ID that Haste will turn into an URI for your
    // convenience:
    $objForm->setFormActionFromPageId(42);

    // Now let's add form fields:
    $objForm->addFormField('year', array(
        'label'         => 'Year',
        'inputType'     => 'text',
        'eval'          => array('mandatory'=>true, 'rgxp'=>'digit')
    ));

    // Need a checkbox?
    $objForm->addFormField('termsOfUse', array(
        'label'         => array('This is the <legend>', 'This is the <label>'),
        'inputType'     => 'checkbox',
        'eval'          => array('mandatory'=>true)
    ));

    // Let's add  a submit button
    $objForm->addFormField('submit', array(
      'label'     => 'Submit form',
      'inputType' => 'submit'
    ));

    // For the ease of use we do provide two helpers for the submit button and captcha field
    $objForm->addSubmitFormField('submit', 'Submit form');
    $objForm->addCaptchaFormField('captcha');

```

### Generating the form
Now that you have your form instance ready, you can generate the markup for it
and validate the user inputs etc.

```php
<?php

    // validate() also checks whether the form has been submitted
    if ($objForm->validate()) {

		// Get the submitted and parsed data:
		$arrData = $this->getData();

		// Get the raw data:
		$arrData = $this->getData(true);

        // Read from POST: \Input::post('year');
        // Read from GET: \Input::get('year');
    }

    // Get the form as string
    echo $objForm->generateAsString();
    // or just
    echo $objForm;

    // You can also pass your own Template instance
    $objMyTemplate = new \FrontendTemplate('mytemplate');
    $objForm->addToTemplate($objMyTemplate);
    echo $objMyTemplate->parse();
```

### Add the form fields from a back end DCA

```php
<?php
    // you can exclude or modify certain fields by passing a callable as second
    // parameter
    $objForm->addFieldsFromDca('tl_content', function(&$arrFields) {
        unset($arrFields['idontwantyou']);
    });
```

### Add the form fields from a form generator form ID

```php
<?php
    // you can exclude or modify certain fields by passing a callable as second
    // parameter
    $objForm->addFieldsFromFormGenerator(42, function(&$arrFields) {
        unset($arrFields['idontwantyou']);
    });
```

### Removing fields on a form instance

```php
<?php
    $objForm->removeFormField('firstname');
```