# Form component

This component is designed to ease working with Contao forms in the front end.


## Usage

The simplest copy-paste form example can be found below. For more detailed explanations, see examples below.

```php
use Codefog\HasteBundle\Form;

$form = new Form('my-form-' . $model->id, 'POST');
$form->addContaoHiddenFields();

$form->addFormField('field_name', [
    // … field config …
]);

$form->addCaptchaFormField();
$form->addSubmitFormField('Submit my form!');

if ($form->validate()) {
    // … process the form …
}

$template->form = $form->getHelperObject();
```


## Examples

A lot of the following examples can be combined. For more internal details, please read the source.

### Preparing a form instance

```php
// Form with the "POST" method
$form = new Form('my-form-' . $model->id, 'POST');

// Form with the "GET" method and a custom "submit check" callback
$form = new Form('my-form-' . $model->id, 'GET', fn() => \Contao\Input::get('foo') === 'bar');

// You can optionally preserve the current GET parameters, and they will be added as hidden fields to the current form.
// This is especially useful when using multiple GET forms (like search and filter).
$form->preserveGetParameters();
$form->preserveGetParameters(['page_n']); // Exclude 'page_n' URL parameter

// By default, the form action is the current request URI you place your form on, but you can either set your own URI:
$form->setAction('https://foo.bar/somewhere.html');

// … or you can set it directly from the page ID:
$form->setActionFromPageId(42);

// Add a captcha field
$form->addCaptchaFormField();

// Automatically add the FORM_SUBMIT and REQUEST_TOKEN hidden fields.
// DO NOT use this method with generate() as the "form" template provides those fields by default.
$form->addContaoHiddenFields();

// Add a sample text form field:
$form->addFormField('year', [
    'label' => &$GLOBALS['TL_LANG']['MSC']['year'],
    'inputType' => 'text',
    'eval' => ['mandatory' => true, 'rgxp' => 'digit', 'minlength' => 4, 'maxlength' => 4],
]);

// Add a sample checkbox:
$form->addFormField('termsOfUse', [
    'label' => &$GLOBALS['TL_LANG']['MSC']['termsOfUse'], // ['This is the <legend>', 'This is the <label>']
    'inputType' => 'checkbox',
    'eval' => ['mandatory' => true],
]);

// Add a submit field
$form->addSubmitFormField($GLOBALS['TL_LANG']['MSC']['submitLabel']);
```


### Generating the form

Now that you have your form instance ready, you can generate the markup for it and validate the user inputs:

```php
<?php

// … see above …

// validate() checks whether the form has been submitted and contains no errors
if ($form->validate()) {
    // Get the submitted and parsed data of a field (only works with POST):
    $year = $form->fetch('year');
    
    // To obtain the values from the GET form, you can use the \Contao\Input class. Note, that you have to validate
    // the input yourself! 
    $year = \Contao\Input::get('year');

    // Get all the submitted and parsed data (only works with POST):
    $formData = $form->fetchAll();

    // For your convenience you can also use a callable to walk over all widgets
    $formData = $form->fetchAll(static fn(string $fieldName, Widget $widget) => \Contao\Input::postRaw($fieldName));
}

// Generate the form as a string
$template->form = $form->generate();

// Add the form directly to the template
$form->addToObject($template);

// Get the form as \stdClass helper object
$this->Template->form = $form->getHelperObject();
```


### Add the form fields directly from a DCA

```php
// Uou can exclude or modify certain fields by passing a callable as second parameter
$form->addFieldsFromDca('tl_content', static function(string &$fieldName, array &$fieldConfig) {
    // make sure to skip elements without inputType, or you will get an exception
    if (!isset($fieldConfig['inputType'])) {
        return false;
    }

    // add anything you like
    if ($fieldName === 'myField') {
        $fieldConfig['eval']['mandatory'] = true;
    }

    // Return true to include the field, or false to skip the field
    return true;
});

// By default, you can use the built-in callback that will skip fields without specified inputType,
// which normally would lead to an exception
$form->addFieldsFromDca('tl_content', [$form, 'skipFieldsWithoutInputType']);
```


### Add the form fields from a form generator

```php
// The form ID is the tl_form.id value
$formId = 42;

$form->addFieldsFromFormGenerator($formId, static function(string &$fieldName, array &$fieldConfig) {
    // … same callback as above …

    return true;
});
```


### Add form fields at specific positions

By passing the [\Codefog\HasteBundle\Util\ArrayPosition](Util/ArrayPosition.md) instance as the third parameter, 
you can insert the field at a specific position:

```php
use Codefog\HasteBundle\Util\ArrayPosition;

// This example adds an explanation form field before the existing submit form field
$form->addFormField('mandatory', [
    'inputType' => 'explanation',
    'eval' => ['text' => '<p>Mandatory</p>', 'class' => 'mandatory-label'],
], ArrayPosition::before('submit'));
```


### Bind model to the form

You can easily bind a Contao Model instance to the form to ease working with them. 
The form will try to load and preset the data from the model if there is already some and will also store 
the values to the model. However, it will *not* call `->save()` on the model, so you can still do with it 
whatever you like.

```php
$newsModel = \Contao\NewsModel::findByPk(42);

// Bind the model to the form
$form->setBoundModel($newsModel);

// Add the form fields directly from DCA
$form->addFieldsFromDca('tl_news');

if ($form->validate()) {
    // The model will now contain the changes, so you can save it
    $newsModel->save();
}
```


### Add a field validation

You can add your own field validation using the `addValidator` method or directly in your field using `save_callback`:

```php
// using a validator
$form->addValidator('email', static function(mixed $value, Widget $objWidget, Form $form): mixed {
    if ($value !== 'xxx@yyy.zz') {
        throw new \InvalidArgumentException('Wrong email address!');
    }

    return $value;
});

// directly in you form field
$form->addFormField('email', [
    // …
    'save_callback' => [
        static function(mixed $value): mixed {
            if ($value !== 'xxx@yyy.zz') {
                throw new \InvalidArgumentException('Wrong email address!');
            }
 
            return $value;
        },
    ],
]); 
```
