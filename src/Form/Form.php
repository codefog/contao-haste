<?php

declare(strict_types=1);

namespace Codefog\HasteBundle\Form;

use Codefog\HasteBundle\DcaRelationsManager;
use Codefog\HasteBundle\DoctrineOrmHelper;
use Codefog\HasteBundle\Form\Validator\ValidatorInterface;
use Codefog\HasteBundle\Model\DcaRelationsModel;
use Codefog\HasteBundle\Util\ArrayPosition;
use Contao\ArrayUtil;
use Contao\Controller;
use Contao\DataContainer;
use Contao\Date;
use Contao\Environment;
use Contao\FormFieldModel;
use Contao\FormHidden;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Model;
use Contao\PageModel;
use Contao\System;
use Contao\TemplateLoader;
use Contao\UploadableWidgetInterface;
use Contao\Widget;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class Form
{
    public const STATE_CLEAN = 0;

    public const STATE_DIRTY = 1;

    protected string $formId = '';

    protected string $httpMethod = '';

    protected string $action = '';

    protected string $enctype = 'application/x-www-form-urlencoded';

    protected bool $isSubmitted = false;

    protected bool $hasUploads = false;

    protected object|null $boundEntity = null;

    protected PropertyAccessor|null $propertyAccessor = null;

    protected Model|null $boundModel = null;

    /**
     * @var array<string, array<ValidatorInterface|callable>>
     */
    protected array $validators = [];

    protected int $currentState = self::STATE_CLEAN;

    protected bool $isValid = true;

    /**
     * True if the HTML5 validation should be ignored.
     */
    protected bool $disableHtmlValidation = false;

    /**
     * Form fields in the representation AFTER the Widget::getAttributesFromDca() call.
     */
    protected array $formFields = [];

    /**
     * @var array<Widget>
     */
    protected array $widgets = [];

    /**
     * Input callback.
     *
     * @var callable
     */
    protected $inputCallback;

    public function __construct(string $formId, string $httpMethod, callable|null $submitCheckCallback = null)
    {
        if (is_numeric($formId)) {
            throw new \InvalidArgumentException('You cannot use a numeric form id.');
        }

        $this->formId = $formId;

        if (!\in_array($httpMethod, ['GET', 'POST'], true)) {
            throw new \InvalidArgumentException('The method has to be either GET or POST.');
        }

        // Set the default submit check callback for POST forms
        if (null === $submitCheckCallback && 'POST' === $httpMethod) {
            $submitCheckCallback = static fn (Form $form) => $form->getFormId() === Input::post('FORM_SUBMIT');
        }

        $this->httpMethod = $httpMethod;
        $this->isSubmitted = \is_callable($submitCheckCallback) ? $submitCheckCallback($this) : true;

        // The form action can be set using several helper methods but by default it's
        // just pointing to the current page
        $this->action = Environment::get('requestUri');
    }

    public function getFormId(): string
    {
        return $this->formId;
    }

    public function getHttpMethod(): string
    {
        return $this->httpMethod;
    }

    public function getEnctype(): string
    {
        return $this->enctype;
    }

    public function isDisableHtmlValidation(): bool
    {
        return $this->disableHtmlValidation;
    }

    public function setDisableHtmlValidation(bool $disableHtmlValidation): self
    {
        $this->disableHtmlValidation = $disableHtmlValidation;

        return $this;
    }

    public function isSubmitted(): bool
    {
        return $this->isSubmitted;
    }

    public function setIsSubmitted(bool $isSubmitted): self
    {
        $this->isSubmitted = $isSubmitted;

        return $this;
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function getCurrentState(): int
    {
        return $this->currentState;
    }

    public function getBoundEntity(): object|null
    {
        return $this->boundEntity;
    }

    /**
     * Binds a Doctrine entity to the form. If there is data, haste form will add the
     * present values as default values.
     */
    public function setBoundEntity(object $boundEntity, PropertyAccessor|null $propertyAccessor = null): self
    {
        $this->boundEntity = $boundEntity;
        $this->propertyAccessor = $propertyAccessor ?? new PropertyAccessor();

        return $this;
    }

    public function getBoundModel(): Model|null
    {
        return $this->boundModel;
    }

    /**
     * Binds a model instance to the form. If there is data, haste form will add the
     * present values as default values.
     */
    public function setBoundModel(Model $boundModel): self
    {
        $this->boundModel = $boundModel;

        return $this;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    /**
     * Set the form action from a Contao page ID.
     */
    public function setActionFromPageId(int $id): self
    {
        if (($pageModel = PageModel::findPublishedById($id)) === null) {
            throw new \InvalidArgumentException(\sprintf('The page ID "%s" does not exist', $id));
        }

        $this->action = $pageModel->getFrontendUrl();

        return $this;
    }

    /**
     * Set a callback to fetch the widget input instead of using getPost().
     */
    public function setInputCallback(callable|null $callback = null): self
    {
        $this->inputCallback = $callback;

        return $this;
    }

    /**
     * Check if there are uploads.
     */
    public function hasUploads(): bool
    {
        // We need to create the widgets to know if we have uploads
        $this->createWidgets();

        return $this->hasUploads;
    }

    public function getFormFields(): array
    {
        return $this->formFields;
    }

    /**
     * Check if form has form fields.
     */
    public function hasFormFields(): bool
    {
        return \count($this->formFields) > 0;
    }

    /**
     * Get a form field by a given name.
     */
    public function getFormField(string $fieldName): array
    {
        if (!$this->hasFormField($fieldName)) {
            throw new \InvalidArgumentException(\sprintf('Form field "%s" does not exist!', $fieldName));
        }

        return $this->formFields[$fieldName];
    }

    /**
     * Removes a form field.
     */
    public function removeFormField(string $fieldName): self
    {
        if (!$this->hasFormField($fieldName)) {
            throw new \InvalidArgumentException(\sprintf('Form field "%s" does not exist!', $fieldName));
        }

        unset($this->formFields[$fieldName]);
        $this->currentState = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Checks if there is a form field with a given name.
     */
    public function hasFormField(string $fieldName): bool
    {
        return \array_key_exists($fieldName, $this->formFields);
    }

    /**
     * Returns a widget instance if existing.
     */
    public function getWidget(string $fieldName): Widget
    {
        $this->createWidgets();

        if (!\array_key_exists($fieldName, $this->widgets)) {
            throw new \InvalidArgumentException(\sprintf('Widget "%s" does not exist!', $fieldName));
        }

        return $this->widgets[$fieldName];
    }

    public function getWidgets(): array
    {
        $this->createWidgets();

        return $this->widgets;
    }

    /**
     * Preserve the current GET parameters by adding them as hidden fields.
     */
    public function preserveGetParameters(array $exclude = []): self
    {
        foreach (array_keys($_GET) as $key) {
            if (\in_array($key, $exclude, false) || \array_key_exists($key, $this->formFields)) {
                continue;
            }

            $this->addFormField($key, ['inputType' => 'hidden', 'value' => Input::get($key)]);
        }

        return $this;
    }

    /**
     * Generate the novalidate attribute.
     */
    public function generateNoValidateAttribute(): string
    {
        return $this->disableHtmlValidation ? ' novalidate' : '';
    }

    /**
     * Adds a form field.
     */
    public function addFormField(string $fieldName, array $fieldConfig, ArrayPosition|null $position = null): self
    {
        $this->checkFormFieldNameIsValid($fieldName);

        if (null === $position) {
            $position = ArrayPosition::last();
        }

        // Make sure it has a "name" attribute because it is mandatory
        if (!isset($fieldConfig['name'])) {
            $fieldConfig['name'] = $fieldName;
        }

        // Support default values
        if (!$this->isSubmitted()) {
            if (isset($fieldConfig['default']) && !isset($fieldConfig['value'])) {
                $fieldConfig['value'] = $fieldConfig['default'];
            }

            // Try to load the default value from bound Entity
            if (!($fieldConfig['ignoreEntityValue'] ?? false) && null !== $this->boundEntity) {
                // If the field is a relation, store the value in the helper which will be
                // processed by Doctrine events later on
                if (($fieldConfig['relation']['type'] ?? null) === 'haste-ManyToMany') {
                    $entityId = $this->boundEntity->getId();

                    if ($entityId > 0 && ($table = $this->getTableNameForEntity($this->boundEntity)) !== null) {
                        $fieldConfig['value'] = DcaRelationsModel::getRelatedValues($table, $fieldName, $entityId);
                    }
                } elseif ($this->propertyAccessor->isReadable($this->boundEntity, $fieldName)) {
                    $value = $this->propertyAccessor->getValue($this->boundEntity, $fieldName);

                    // This might be a related object
                    if ($this->hasEntitySingleValuedRelation($this->boundEntity, $fieldName)) {
                        $value = $value?->getId();
                    }

                    // Set the regular value
                    $fieldConfig['value'] = $value;
                }
            }

            // Try to load the default value from bound Model
            if (!($fieldConfig['ignoreModelValue'] ?? false) && null !== $this->boundModel) {
                $fieldConfig['value'] = $this->boundModel->$fieldName;
            }
        }

        if (!isset($fieldConfig['inputType'])) {
            throw new \RuntimeException(\sprintf('You did not specify any inputType for the field "%s"!', $fieldName));
        }

        /** @var class-string<Widget> $className */
        $className = $GLOBALS['TL_FFL'][$fieldConfig['inputType']] ?? null;

        if (!class_exists($className)) {
            throw new \RuntimeException(\sprintf('The class "%s" for type "%s" could not be found.', $className, $fieldConfig['inputType']));
        }

        $rgxp = $fieldConfig['eval']['rgxp'] ?? null;

        // Convert date formats into timestamps
        if (\in_array($rgxp, ['date', 'time', 'datim'], true)) {
            $this->addValidator(
                $fieldName,
                static function (mixed $value) use ($rgxp) {
                    if ($value) {
                        $value = (new Date($value, Date::getFormatFromRgxp($rgxp)))->tstamp;
                    }

                    return $value;
                },
            );
        }

        // If the field is a relation, remove the default load/save callbacks
        if (!($fieldConfig['ignoreEntityValue'] ?? false) && null !== $this->boundEntity && ($fieldConfig['relation']['type'] ?? null) === 'haste-ManyToMany') {
            // Remove the load callbacks
            if (\is_array($fieldConfig['load_callback'] ?? null)) {
                $fieldConfig['load_callback'] = array_values(array_filter($fieldConfig['load_callback'], static fn (array $callback) => DcaRelationsManager::class !== $callback[0]));
            }

            // Remove the save callbacks
            if (\is_array($fieldConfig['save_callback'] ?? null)) {
                $fieldConfig['save_callback'] = array_values(array_filter($fieldConfig['save_callback'], static fn (array $callback) => DcaRelationsManager::class !== $callback[0]));
            }
        }

        // Set the save_callback as validator
        if (\is_array($fieldConfig['save_callback'] ?? null)) {
            $this->addValidator(
                $fieldName,
                function (mixed $value, Widget $widget) use ($fieldConfig, $fieldName) {
                    $dc = $this->createDataContainerMock($value, $fieldName, $widget->name);

                    foreach ($fieldConfig['save_callback'] as $callback) {
                        if (\is_array($callback)) {
                            $value = System::importStatic($callback[0])->{$callback[1]}($value, $dc);
                        } elseif (\is_callable($callback)) {
                            $value = $callback($value, $dc);
                        }
                    }

                    return $value;
                },
            );
        }

        $dc = $this->createDataContainerMock($fieldConfig['value'] ?? null, $fieldName, $fieldConfig['name']);

        // Preserve the label
        $label = $fieldConfig['label'] ?? null;

        // Generate the attributes
        /** @var array $fieldConfig */
        $fieldConfig = $className::getAttributesFromDca($fieldConfig, $fieldConfig['name'], $fieldConfig['value'] ?? null, $fieldName, $dc->table, $dc);

        // Reset the ID to the field name
        $fieldConfig['id'] = $fieldName;

        // Remove the label if it was not set – Contao will set it to field name if
        // it's not present
        if (!isset($label) || !$label) {
            $fieldConfig['label'] = '';
        }

        // Convert optgroups so they work with FormSelectMenu
        if (\is_array($fieldConfig['options'] ?? null) && ArrayUtil::isAssoc($fieldConfig['options'])) {
            $options = $fieldConfig['options'];
            $fieldConfig['options'] = [];

            foreach ($options as $k => $v) {
                if (isset($v['label'])) {
                    $fieldConfig['options'][] = $v;
                } else {
                    $fieldConfig['options'][] = [
                        'label' => $k,
                        'value' => $k,
                        'group' => '1',
                    ];

                    foreach ($v as $vv) {
                        $fieldConfig['options'][] = $vv;
                    }
                }
            }
        }

        $this->formFields = $position->addToArray($this->formFields, [$fieldName => $fieldConfig]);
        $this->currentState = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Add multiple form fields.
     */
    public function addFormFields(array $formFields, ArrayPosition|null $position = null): self
    {
        if (null !== $position && (ArrayPosition::FIRST === $position->position() || ArrayPosition::BEFORE === $position->position())) {
            $formFields = array_reverse($formFields, true);
        }

        foreach ($formFields as $fieldName => $fieldConfig) {
            $this->addFormField($fieldName, $fieldConfig, $position);
        }

        return $this;
    }

    /**
     * Add the Contao hidden fields FORM_SUBMIT and REQUEST_TOKEN.
     */
    public function addContaoHiddenFields(): self
    {
        $this->addFormField('FORM_SUBMIT', [
            'name' => 'FORM_SUBMIT',
            'inputType' => 'hidden',
            'ignoreModelValue' => true,
            'value' => $this->getFormId(),
        ]);

        $tokenName = System::getContainer()->getParameter('contao.csrf_token_name');
        $tokenManager = System::getContainer()->has('contao.csrf.token_manager') ? System::getContainer()->get('contao.csrf.token_manager') : System::getContainer()->get('security.csrf.token_manager');

        $this->addFormField('REQUEST_TOKEN', [
            'name' => 'REQUEST_TOKEN',
            'inputType' => 'hidden',
            'ignoreModelValue' => true,
            'value' => $tokenManager->getToken($tokenName)->getValue(),
        ]);

        return $this;
    }

    /**
     * Helper method to easily add a captcha field.
     */
    public function addCaptchaFormField(string $fieldName = 'captcha', ArrayPosition|null $position = null): self
    {
        $this->addFormField(
            $fieldName,
            [
                'name' => $fieldName.'_'.$this->formId, // make sure they're unique on a page
                'label' => &$GLOBALS['TL_LANG']['MSC']['securityQuestion'],
                'inputType' => 'captcha',
                'eval' => ['mandatory' => true],
            ],
            $position,
        );

        return $this;
    }

    /**
     * Helper method to easily add a submit field.
     */
    public function addSubmitFormField(string $label, string $fieldName = 'submit', ArrayPosition|null $position = null): self
    {
        $this->addFormField(
            $fieldName,
            [
                'name' => $fieldName,
                'label' => $label,
                'inputType' => 'submit',
            ],
            $position,
        );

        return $this;
    }

    /**
     * Add form fields from a back end DCA.
     */
    public function addFieldsFromDca(string $table, callable|null $callback = null): self
    {
        System::loadLanguageFile($table);
        Controller::loadDataContainer($table);

        $fieldConfigs = &$GLOBALS['TL_DCA'][$table]['fields'];

        foreach (($fieldConfigs ?? []) as $k => $v) {
            // Unset the backend attributes that can add unnecessary JavaScript code (#206)
            unset($v['eval']['submitOnChange']);

            if (\is_callable($callback) && !$callback(...[&$k, &$v])) {
                continue;
            }

            $this->addFormField($k, $v);
        }

        return $this;
    }

    /**
     * Return true if the field has "inputType" set, false otherwise.
     *
     * This is a default callback that can be used with addFieldsFromDca() method. It
     * prevents from adding fields that do not have inputType specified which would
     * result in an exception. The fields you typically would like to skip are: id,
     * tstamp, pid, sorting.
     */
    public function skipFieldsWithoutInputType(string $fieldName, array $fieldConfig): bool
    {
        return isset($fieldConfig['inputType']);
    }

    /**
     * Add form fields from a back end form generator form ID.
     */
    public function addFieldsFromFormGenerator(int $formId, callable|null $callback = null): self
    {
        if (null === ($objFields = FormFieldModel::findPublishedByPid($formId))) {
            throw new \InvalidArgumentException('Form ID "'.$formId.'" does not exist or has no published fields.');
        }

        foreach ($objFields as $objField) {
            // make sure "name" is set because not all form fields do need it and it would
            // thus overwrite the array indexes
            $fieldName = $objField->name ?: 'field_'.$objField->id;

            $this->checkFormFieldNameIsValid($fieldName);

            $fieldConfig = $objField->row();

            // Make sure it has a "name" attribute because it is mandatory
            if (!isset($fieldConfig['name'])) {
                $fieldConfig['name'] = $fieldName;
            }

            if (\is_callable($callback) && !$callback(...[&$fieldName, &$fieldConfig])) {
                continue;
            }

            $this->formFields[$fieldName] = $fieldConfig;
        }

        $this->currentState = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Adds a form field from the form generator without trying to convert a DCA
     * configuration.
     */
    public function addFieldFromFormGenerator(string $fieldName, array $fieldConfig, ArrayPosition|null $position = null): self
    {
        $this->checkFormFieldNameIsValid($fieldName);

        if (null === $position) {
            $position = ArrayPosition::last();
        }

        // make sure "name" is set because not all form fields do need it and it would
        // thus overwrite the array indexes
        $fieldName = $fieldConfig['name'] ?: 'field_'.$fieldConfig['id'];

        // Make sure it has a "name" attribute because it is mandatory
        if (!isset($fieldConfig['name'])) {
            $fieldConfig['name'] = $fieldName;
        }

        $this->formFields = $position->addToArray($this->formFields, [$fieldName => $fieldConfig]);
        $this->currentState = self::STATE_DIRTY;

        return $this;
    }

    /**
     * Add a validator to the form field.
     */
    public function addValidator(string $fieldName, ValidatorInterface|callable $validator): self
    {
        $this->validators[$fieldName][] = $validator;

        return $this;
    }

    /**
     * Create the widget instances.
     */
    public function createWidgets(): self
    {
        if (self::STATE_CLEAN === $this->currentState) {
            return $this;
        }

        // Reset to initial values
        $this->widgets = [];
        $this->hasUploads = false;

        // Initialize widgets
        foreach ($this->formFields as $fieldName => $fieldConfig) {
            $className = $GLOBALS['TL_FFL'][$fieldConfig['type']] ?? '';

            if (!class_exists($className)) {
                throw new \RuntimeException(\sprintf('The class "%s" for type "%s" could not be found.', $className, $fieldConfig['type']));
            }

            // Some widgets render the mandatory asterisk only based on "require" attribute
            if (!isset($fieldConfig['required'])) {
                $fieldConfig['required'] = (bool) ($fieldConfig['mandatory'] ?? false);
            }

            $widget = new $className($fieldConfig);

            if ($widget instanceof UploadableWidgetInterface) {
                $this->hasUploads = true;
            }

            $this->widgets[$fieldName] = $widget;
        }

        $this->currentState = self::STATE_CLEAN;

        if ($this->hasUploads()) {
            if ('GET' === $this->getHttpMethod()) {
                throw new \RuntimeException('The HTTP method "GET" is not supported for file uploads');
            }

            $this->enctype = 'multipart/form-data';
        } else {
            $this->enctype = 'application/x-www-form-urlencoded';
        }

        return $this;
    }

    /**
     * Validate the form.
     */
    public function validate(): bool
    {
        if (!$this->isSubmitted()) {
            return false;
        }

        $this->createWidgets();
        $this->isValid = true;

        foreach ($this->widgets as $fieldName => $widget) {
            if (null !== $this->inputCallback && method_exists($widget, 'setInputCallback')) {
                $widget->setInputCallback(fn () => ($this->inputCallback)($fieldName, $widget));
            }

            $widget->validate();

            if ($widget->hasErrors()) {
                $this->isValid = false;
            } elseif ($widget->submitInput()) {
                $value = $widget->value;

                // Run custom validators
                if (isset($this->validators[$fieldName])) {
                    try {
                        foreach ($this->validators[$fieldName] as $validator) {
                            if ($validator instanceof ValidatorInterface) {
                                $value = $validator->validate($value, $widget, $this);
                            } else {
                                $value = $validator($value, $widget, $this);
                            }
                        }
                    } catch (\Exception $e) {
                        $widget->class = 'error';
                        $widget->addError($e->getMessage());
                        $this->isValid = false;
                    }
                }

                if ($widget->hasErrors()) {
                    // Re-check the status in case a custom validator has added an error
                    $this->isValid = false;

                    continue;
                }

                // Bind to Entity instance
                if (null !== $this->boundEntity) {
                    // If the field is a relation, store the value in the helper which will be
                    // processed by doctrine events later on
                    if (null !== ($table = $this->getTableNameForEntity($this->boundEntity)) && ($GLOBALS['TL_DCA'][$table]['fields'][$fieldName]['relation']['type'] ?? null) === 'haste-ManyToMany') {
                        /** @var DoctrineOrmHelper $doctrineHelper */
                        $doctrineHelper = System::getContainer()->get(DoctrineOrmHelper::class);
                        $doctrineHelper->addEntityRelatedValues($this->boundEntity, $fieldName, (array) $value);

                        continue;
                    }

                    // Set the regular value, if writable
                    if ($this->propertyAccessor->isWritable($this->boundEntity, $fieldName)) {
                        // This might be a related object
                        if ($this->hasEntitySingleValuedRelation($this->boundEntity, $fieldName)) {
                            $value = $this->getEntityManager()->getRepository($this->getMetaDataForEntity($this->boundEntity)->getAssociationTargetClass($fieldName))->find($value);
                        }

                        $this->propertyAccessor->setValue($this->boundEntity, $fieldName, $value);
                    }
                }

                // Bind to Model instance
                if (null !== $this->boundModel) {
                    $this->boundModel->$fieldName = $value;
                }
            }
        }

        return $this->isValid();
    }

    /**
     * Add form to an object.
     */
    public function addToObject(object $objObject): self
    {
        $this->createWidgets();

        $tokenName = System::getContainer()->getParameter('contao.csrf_token_name');
        $tokenManager = System::getContainer()->has('contao.csrf.token_manager') ? System::getContainer()->get('contao.csrf.token_manager') : System::getContainer()->get('security.csrf.token_manager');

        $objObject->action = $this->getAction();
        $objObject->formId = $this->getFormId();
        $objObject->requestToken = $tokenManager->getToken($tokenName)->getValue();
        $objObject->method = strtolower($this->getHttpMethod());
        $objObject->enctype = $this->getEnctype();
        $objObject->widgets = $this->widgets;
        $objObject->valid = $this->isValid();
        $objObject->submitted = $this->isSubmitted();
        $objObject->hasUploads = $this->hasUploads();
        $objObject->novalidate = $this->generateNoValidateAttribute();

        $widgets = ['hidden' => [], 'visible' => []];

        // Split hidden and visigle widgets
        foreach ($this->widgets as $k => $widget) {
            $widgets[$widget instanceof FormHidden ? 'hidden' : 'visible'][$k] = $widget;
        }

        $objObject->hidden = '';

        // Generate hidden form fields
        foreach ($widgets['hidden'] as $widget) {
            $objObject->hidden .= $widget->parse();
        }

        $objObject->fields = '';

        // Generate visible form fields
        foreach ($widgets['visible'] as $widget) {
            $objObject->fields .= $widget->parse();
        }

        $objObject->hiddenWidgets = $widgets['hidden'];
        $objObject->visibleWidgets = $widgets['visible'];

        $objObject->hasteFormInstance = $this;

        return $this;
    }

    /**
     * Get the form helper object.
     */
    public function getHelperObject(): \stdClass
    {
        $helper = new \stdClass();
        $this->addToObject($helper);

        return $helper;
    }

    /**
     * Generate a form and return it as HTML string.
     */
    public function generate(string|null $templateName = null): string
    {
        if (null === $templateName) {
            $templateName = 'form';

            try {
                TemplateLoader::getPath($templateName, 'html5');
            } catch (\Exception) {
                $templateName = 'form_wrapper';
            }
        }

        $template = new FrontendTemplate($templateName);
        $template->class = 'hasteform_'.$this->getFormId();
        $template->formSubmit = $this->getFormId();

        $this->addToObject($template);

        return $template->parse();
    }

    /**
     * Return the submitted data of a specific form field.
     */
    public function fetch(string $fieldName): mixed
    {
        if (!$this->isSubmitted()) {
            throw new \BadMethodCallException('The has been not submitted');
        }

        if (!$this->inputCallback && 'POST' !== $this->getHttpMethod()) {
            throw new \BadMethodCallException('Widgets only support fetching POST values. Use the \Contao\Input class for other purposes.');
        }

        if (!isset($this->widgets[$fieldName])) {
            throw new \InvalidArgumentException('The widget with name "'.$fieldName.'" does not exist.');
        }

        $widget = $this->widgets[$fieldName];

        // Support file uploads in Contao 5
        if ($widget instanceof UploadableWidgetInterface) {
            return $widget->value;
        }

        if (!$widget->submitInput()) {
            // Do not throw exception here for BC
            return null;
        }

        return $widget->value;
    }

    /**
     * Return the submitted data as an associative array.
     */
    public function fetchAll(callable|null $callback = null): array
    {
        $data = [];

        foreach ($this->widgets as $fieldName => $widget) {
            // Do not check $widget->submitInput() here because the callback could
            // handle it differently
            if (\is_callable($callback)) {
                $value = $callback($fieldName, $widget);
            } else {
                $value = $this->fetch($fieldName);
            }

            if (null !== $value) {
                $data[$fieldName] = $value;
            }
        }

        return $data;
    }

    /**
     * Check for a valid form field name.
     */
    protected function checkFormFieldNameIsValid(string $fieldName): void
    {
        if (is_numeric($fieldName)) {
            throw new \InvalidArgumentException('You cannot use a numeric form field name.');
        }

        if (\in_array($fieldName, array_keys($this->formFields), true)) {
            throw new \InvalidArgumentException(\sprintf('"%s" has already been added to the form.', $fieldName));
        }
    }

    /**
     * Create the data container mock.
     */
    private function createDataContainerMock(mixed $value, string $field, string $name): DataContainer
    {
        return new class($this->getBoundModel(), $value, $field, $name) extends DataContainer {
            public function __construct(Model|null $model, mixed $value, string $field, string $name)
            {
                $this->id = $model ? $model->id : 0;
                $this->table = $model ? $model::getTable() : '';
                $this->value = $value;
                $this->field = $field;
                $this->inputName = $name;
                $this->activeRecord = $model; // BC

                parent::__construct();
            }

            public function getCurrentRecord(int|string|null $id = null, string|null $table = null): array|null
            {
                if (!$id) {
                    return null;
                }

                $record = $this->Database
                    ->prepare("SELECT * FROM $table WHERE id=?")
                    ->limit(1)
                    ->execute($id)
                ;

                return $record->numRows ? $record->row() : null;
            }

            /**
             * @return string|void
             */
            public function getPalette()
            {
                // noop
            }

            protected function save($varValue): void
            {
                // noop
            }
        };
    }

    /**
     * Get the table name for entity.
     */
    private function getTableNameForEntity(object $entity): string|null
    {
        return $this->getMetaDataForEntity($entity)?->getTableName();
    }

    /**
     * Return true if the entity has a single valued relation for the given field.
     */
    private function hasEntitySingleValuedRelation(object $entity, string $field): bool
    {
        if (($metaData = $this->getMetaDataForEntity($entity)) === null) {
            return false;
        }

        return $metaData->isSingleValuedAssociation($field);
    }

    /**
     * Get the meta data for entity.
     *
     * @template T of object
     *
     * @param T $entity
     *
     * @return ClassMetadata<T>|null
     */
    private function getMetaDataForEntity(object $entity): ClassMetadata|ClassMetadataInfo|null
    {
        static $cache = [];

        $className = $entity::class;

        if (!\array_key_exists($className, $cache)) {
            $cache[$className] = $this->getEntityManager()?->getClassMetadata($entity::class);
        }

        return $cache[$className];
    }

    /**
     * Get the entity manager.
     */
    private function getEntityManager(): EntityManager|null
    {
        $service = 'doctrine.orm.entity_manager';

        if (!System::getContainer()->has($service)) {
            return null;
        }

        return System::getContainer()->get($service);
    }
}
