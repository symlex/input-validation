Easy & secure whitelist validation for PHP
==========================================

[![Build Status](https://travis-ci.org/symlex/input-validation.png?branch=master)](https://travis-ci.org/symlex/input-validation)
[![Latest Stable Version](https://poser.pugx.org/symlex/input-validation/v/stable.svg)](https://packagist.org/packages/symlex/input-validation)
[![Total Downloads](https://poser.pugx.org/symlex/input-validation/downloads.svg)](https://packagist.org/packages/symlex/input-validation)
[![License](https://poser.pugx.org/symlex/input-validation/license.svg)](https://packagist.org/packages/symlex/input-validation)

**This library provides whitelist validation ("accept known good") that is perfectly suited for building secure REST services.** It uses programming language independent validation rules (plain array) that can be reused for additional client-side validation (JavaScript) or passed to template rendering engines such as Twig. By design, it is compatible with any framework and input source (HTML, REST, RPC, ...).

A major advantage of this data source agnostic approach is that developers can do bottom-up development using unit tests to find bugs early and work on validation rules without an existing HTML frontend or storage backend. Use case specific input value validation is also more secure than general model validation, which often relies on a blacklist ("reject known bad").

Besides basic validation rules such as type or length, more advanced features are supported as well - for example dependent fields, internationalization and multi-page forms. Validated values can be fetched individually, as flat array, by tag or by page.

The usage is simple: Form classes can inherit their definitions from each other. If needed, validation behavior can be changed using standard object-oriented methodologies. You don't need to hold a PhD in design patterns to understand how it works.

Basic example
-------------

This example shows how to validate user input in a REST controller context. Note, how easy it is to avoid the deeply nested structures you often find in validation code. User model and form are injected as dependencies. 

```php
class UserController
{
    protected $user;
    protected $form;

    public function __construct(UserModel $user, UserForm $form)
    {
        $this->user = $user;
        $this->form = $form;
    }
    
    public function putAction(int $id, Request $request): array // Update
    {
        $this->user->find($id); // Find entity (throws exception, if not found)
        
        $this->form->setDefinedValues($this->user->getValues()); // Initialization
        $this->form->setDefinedWritableValues($request->request->all()); // Input values
        $this->form->validate(); // Validation

        if($this->form->hasErrors()) {
            throw new FormInvalidException($this->form->getFirstError());
        }
        
        $this->user->update($this->form->getValues()); // Update values

        return $this->user->getValues(); // Return updated entity values
    }
    
    public function optionsAction(int $id): array // Describe form fields for user resource
    {
        $this->user->find($id); // Find entity (throws exception, if not found)
        
        $this->form->setDefinedValues($this->user->getValues()); // Initialization
        
        return $this->form->getAsArray(); // Returns form as JSON compatible array incl all values
    }
}
```

See also [Doctrine ActiveRecord - Object-oriented CRUD for Doctrine DBAL](https://github.com/symlex/doctrine-active-record)

Configuration
-------------
A detailed overview of field properties can be found below. `$_('label')` is used for optional translation of field captions or other strings - a number of different translation file formats such as YAML are supported for that (see [Symfony Components](http://symfony.com/doc/current/components/translation.html) documentation)

```php
use InputValidation\Form;

class UserForm extends Form
{
    protected function init(array $params = array())
    {
        $definition = [
            'username' => [
                'type' => 'string',
                'caption' => $this->_('username'),
                'required' => true,
                'min' => 3,
                'max' => 15
            ],
            'email' => [
                'type' => 'email',
                'caption' => $this->_('email_address'),
                'required' => true
            ],
            'gender' => [
                'type' => 'string',
                'caption' => $this->_('gender'),
                'required' => false,
                'options' => [
                    'm' => 'Male',
                    'f' => 'Female',
                    'o' => 'Other'
                ],
                'optional' => true
            ],
            'birthday' => [
                'type' => 'date',
                'caption' => $this->_('birthday'),
                'required' => false
            ],
            'password' => [
                'type' => 'string',
                'caption' => $this->_('password'),
                'required' => true,
                'min' => 5,
                'max' => 30
            ],
            'password_again' => [
                'type' => 'string',
                'caption' => $this->_('password_again'),
                'required' => true,
                'matches' => 'password'
            ],
            'continent' => [
                'type' => 'string',
                'caption' => $this->_('region'),
                'required' => true,
                'options' => [
                    'north_america' => 'North America',
                    'south_america' => 'South Amertica',
                    'europe' => 'Europe,
                    'asia' => 'Asia',
                    'australia' => 'Australia'
                ]
            ]
        ];

        $this->setDefinition($definition);
    }
}
```

Field properties
----------------

Property               | Description
---------------------- | ---------------------------------------------------------------------------------------------------
caption                | Field title (used for form rendering and in validation messages)
type                   | Data type: int, numeric, scalar, list, bool, string, email, ip, url, date, datetime, time and switch
type_params            | Optional parameters for data type validation
options                | Array of possible values for the field (for select lists or radio button groups)
min                    | Minimum value for numbers/dates, length for strings or number of elements for lists
max                    | Maximum value for numbers/dates, length for strings or number of elements for lists
required               | Field cannot be empty (if false, setDefinedValues() and setDefinedWritableValues() still throw an exception, if it does not exist at all)
optional               | setDefinedValues() and setDefinedWritableValues() don't throw an exception, if the field is missing in the input values (usefull for checkboxes or certain JavaScript frameworks, that do not submit any data for empty form elements e.g. AngularJS)
readonly               | User is not allowed to change the field (not writable)
hidden                 | User can not see the field (no impact on the validation)
default                | Default value
regex                  | Regular expression to match against
matches                | Field value must match another form field (e.g. for password or email validation). Property can be prefixed with "!" to state that the fields must be different.
depends                | Field is required, if the given form field is not empty
depends_value          | Field is required, if the field defined in "depends" has this value
depends_value_empty    | Field is required, if the field defined in "depends" is empty
depends_first_option   | Field is required, if the field defined in "depends" has the first value (see "options")
depends_last_option    | Field is required, if the field defined in "depends" has the last value (see "options")
page                   | Page number for multi-page forms
tags                   | Optional list of tags (can be used to extract values by tag, see getValuesByTag())

Creating new instances
----------------------
You can create new form instances manually...

```php
use InputValidation\Form;
use InputValidation\Form\Validator;
use InputValidation\Form\Options\YamlOptions;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Loader\ArrayLoader;

$translator = new Translator('en', new MessageSelector);
$translator->addLoader('yaml', new YamlFileLoader);
$translator->addLoader('array', new ArrayLoader);

$validator = new Validator();

$options = new YamlOptions($translator);

$form = new Form($translator, $validator, $options);
```

... or using the convenient `InputValidation\Form\Factory`:

```php
$formFactory = new InputValidation\Form\Factory($translator, $validator, $options);
$formFactory->setFactoryNamespace('App\Form');
$formFactory->setFactoryPostfix('Form');
$formFactory->create('User'); // Returns instance of App\Form\UserForm
```

Composer
--------

If you are using composer, simply add "symlex/input-validation" to your composer.json file and run `composer update`:

```
"require": {
    "symlex/input-validation": "^3.0"
}
```

API
---

**__construct(Translator $translator, Form\Validator $validator, Form\OptionsInterface $options, array $params = array())**

The constructor requires instances of Symfony\Component\Translation\TranslatorInterface, InputValidation\Form\Validator, InputValidation\Form\OptionsInterface and an optional set of arbitrary parameters, which are passed to **init(array $params = array())** (protected method for initializing the form).

**setOptions(Form\OptionsInterface $options)**

Sets an optional class instance to automatically fill option lists (see "options" form field property). OptionsInterface only requires a method get($listName) that returns an array of options.

**getOptions(): Form\OptionsInterface**

Returns the options instance 

**options(string $listName): array**

Returns a list of options e.g. countries:

```php
'country' => array(
    'type' => 'string',
    'caption' => 'Country',
    'default' => 'DE',
    'options' => $this->form->options('countries')
)
```

**optionsWithDefault(string $listName, string $defaultLabel = ''): array**

Returns a list of options with default label for no selection

```php
'country' => array(
    'type' => 'string',
    'caption' => 'Country',
    'required' => true,
    'options' => $this->form->optionsWithDefault('countries')
)
```
     
**getTranslator(): Translator**

Returns the Translator instance (see __construct)

**setTranslator(Translator $translator)**

Sets the Translator instance (see __construct)

**getValidator(): Validator**

Returns the Validator instance (see __construct)

**setValidator(Validator $validator)**

Sets the Validator instance (see __construct)

**getLocale()**

Returns the current locale e.g. en, de or fr

**setLocale($locale)**

Sets the current locale e.g. en, de or fr

**setDefinition(array $definition)**

Sets the form field definition array (see example and form field properties)

**getDefinition($key = null, $propertyName = null): mixed**

Returns the form field definition(s). If $key is null, definitions for all fields are returned. If $propertyName is null and $key is not null, only the definition of the given field key are returned. If both arguments are not null, only the definition of the given form field property is returned (for example, getDefinition('firstname', 'type') might return 'string'). A FormException is thrown otherwise.

**getFieldDefinition(string $name): array**

Returns form field definition as array (wrapper for getDefinition)

**getFieldProperty(string $name, string $property): mixed**

Returns a field property from the form definition (wrapper for getDefinition)

**addDefinition($key, array $definition)**

Adds a single form field definition (see form field properties)

**changeDefinition($key, array $changes)**

Changes a single form field definition (see form field properties)

**setGroups(array $groups)**

Sets form field groups (optional feature, if you want to reuse your form definition to reder the form as HTML).

Example:
```php
$form->setGroups(
    array(
        'name' => array('firstname', 'lastname'),
        'address' => array('street', 'housenr', 'zip', 'city')
    )
);
```

**getFieldAsArray(string $key): array**

Returns field definition and value as JSON compatible array:

```php
array (
  'name' => 'country',
  'caption' => 'Country',
  'default' => 'DE',
  'type' => 'string',
  'options' => array (
    array (
      'option' => 'US',
      'label' => 'United States',
    ),
    array (
      'option' => 'GB',
      'label' => 'United Kingdom',
    ),
    array (
      'option' => 'DE',
      'label' => 'Germany',
    ),
  ),
  'value' => 'DE',
  'uid' => 'id58a401f5a54e6',
),
```

**getAsArray(): array**

Returns the complete form (definition and all values) as JSON compatible array, which can be used to render the form in templates:

```php
array (
  'company' => array (
    'name' => 'company',
    'caption' => 'Company',
    'type' => 'string',
    'value' => 'IBM',
    'uid' => 'id58a401f5a54d6',
  ),
  'country' => array (
    'name' => 'country',
    'caption' => 'Country',
    'default' => 'DE',
    'type' => 'string',
    'options' => array (
      array (
        'option' => 'US',
        'label' => 'United States',
      ),
      array (
        'option' => 'GB',
        'label' => 'United Kingdom',
      ),
      array (
        'option' => 'DE',
        'label' => 'Germany',
      ),
    ),
    'value' => 'DE',
    'uid' => 'id58a401f5a54e6',
  ),
),
```

**getAsGroupedArray(): array**

Returns grouped form field definitions and values (you must use setGroups() first):

```php
array(
  'person' => array (
    'group_name' => 'person',
    'group_caption' => 'Person',
    'fields' => array(
      'person' => array (
        'name' => 'firstname',
        'caption' => 'First Name',
        'type' => 'string',
        'readonly' => true,
        'value' => NULL,
        'uid' => 'id58a401f5a5267',
      ),
      'lastname' => array (
        'name' => 'lastname',
        'caption' => 'Last Name',
        'type' => 'string',
        'readonly' => false,
        'value' => 'Mander',
        'uid' => 'id58a401f5a5279',
      ),
    ),
  ),
  'location' => array (
    'group_name' => 'location',
    'group_caption' => 'Location',
    'fields' => array (
      'company' => array (
        'name' => 'company',
        'caption' => 'Company',
        'type' => 'string',
        'value' => 'IBM',
        'uid' => 'id58a401f5a54d6',
      ),
      'country' => array (
        'name' => 'country',
        'caption' => 'Country',
        'type' => 'string',
        'default' => 'DE',
        'options' => array (
          array (
            'option' => 'US',
            'label' => 'United States',
          ),
          array (
            'option' => 'GB',
            'label' => 'United Kingdom',
          ),
          array (
            'option' => 'DE',
            'label' => 'Germany',
          ),
        ),
        'value' => 'DE',
        'uid' => 'id58a401f5a54e6',
      ),
    ),
  ),
),
```

**setAllValues(array $values)**

Sets all form values (does not check, if the fields exist or if the fields are writable by the user). Throws an exception, if you try to set values for undefined fields.

**setDefinedValues(array $values)**

Iterates through the form definition and sets the values for fields, that are present in the form definition.

**setWritableValues(array $values)**

Iterates through the passed value array and sets the values for fields, that are writable by the user.

**setDefinedWritableValues(array $values)**

Sets the values for fields, that are present in the form definition and that are writable by the user (recommended method for most use cases).

**setWritableValuesOnPage(array $values, $page)**

Sets the values for fields on the given page, that are present in the form definition and that are writable by the user (recommended method for most use cases, if the form contains multiple pages).

**getValuesByPage()**

Returns the form values for all elements grouped by page.

**getValuesByTag($tag)**

Returns the form values for all elements by tag (see "tags" form field property)

**getValues()**

Returns all form field values

**getWritableValues()**

Returns all user writable form field values

**translate($token, array $params = array())**

Uses the Translator adapter to translate the given string/token (accepts optional parameters for the translation string).

**_($token, array $params = array())**

Alias for translate()

**addError($key, $token, array $params = array())**

Adds a validation error (uses translate() for the error message internally)

**validate()**

Validates all form field values. You can use getErrors(), getErrorsByPage(), isValid() and hasErrors() to get the validation results.

**hasErrors(): bool**

Returns true, if the form has errors

**isValid(): bool**

eturns true, if the form is valid (has no errors)

**getErrors(): array**

Returns all errors and throws an exception, if the validation was not performed yet (you must call validate() before calling getErrors()).

**getFirstError(): string**

Returns the first error as string

**getErrorsAsText(): string**

Returns all errors as indented text (for command line applications)

**getErrorsByPage(): array**

Returns all errors grouped by page and throws an exception, if the validation was not performed yet (you must call validate() before calling getErrorsByPage()).

**clearErrors()**

Resets the validation and clears all errors

**getHash(): string**

Returns hash that uniquely identifies the form (for caching comprehensive forms)

Form Validation vs Model Validation
-----------------------------------
The following visualization highlights the differences between client-side, input value (form) and model validation. Model validation generally operates on **trusted** data (internal system state) and should be **repeatable** at any point in time while input validation explicitly operates **once** on data that comes from **untrusted** sources (depending on the use case and user privileges). This separation makes it possible to build reusable models, controllers and forms that can be coupled through dependency injection (see REST controller example at the top).

Think of input validation as **whitelist** validation ("accept known good") and model validation as **blacklist** validation ("reject known bad"). Whitelist validation is more secure while blacklist validation prevents your model layer from being overly constrained to very specific use cases.

Invalid model data should always cause an **exception** to be thrown (otherwise the application can continue running without noticing the mistake) while invalid input values coming from external sources are **not unexpected**, but rather common (unless you got users that never make mistakes). Validation within a specific model may not be possible at all, if a set of input values must be validated together (because they depend on each other) but individual values are then stored in different models - at least it can create **additional dependencies** between models that would not be there otherwise up to the point that all models depend on each other. In short: The application may still work as expected, but the code is a mess.

From a theoretical standpoint, any complex system has more **internal state** than it exposes to the outside, thus it is never sufficient to use model validation only - except the model provides two sets of methods: some that are used internally and some that can be exposed to arbitrary input data from any source. Aside from side-effects such as limited user feedback (exception messages) and bloated model code, this approach may easily lead to serious security flaws. Malicious input data is a much higher threat to **multi-user** Web applications than to classical **single-user** desktop applications. Simple blacklist model validation may be fully sufficient for desktop applications, which are in full control of the user interface (view layer).

Client-side (JavaScript or HTML) form validation is always just a convenience feature and **not reliable**. However, with this library you can (at least partly) **reuse existing server-side form validation** rules to perform client-side validation, since they can be easily converted to JSON (for JavaScript) or be passed to template rendering engines such as Twig or Smarty (for HTML). Reusing model layer validation rules in a similar fashion is at least difficult, if not impossible.

See also: https://www.owasp.org/index.php/Data_Validation#Where_to_include_business_rule_validation

![Differences between client-side, input value (form) and model validation](https://www.lucidchart.com/publicSegments/view/5461f867-ae1c-44a4-b565-6f780a00cf27/image.png)
