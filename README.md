Secure whitelist validation for PHP
===================================

[![Build Status](https://travis-ci.org/lastzero/php-input-validation.png?branch=master)](https://travis-ci.org/lastzero/php-input-validation)
[![Latest Stable Version](https://poser.pugx.org/lastzero/php-input-validation/v/stable.svg)](https://packagist.org/packages/lastzero/php-input-validation)
[![License](https://poser.pugx.org/lastzero/php-input-validation/license.svg)](https://packagist.org/packages/lastzero/php-input-validation)

This library provides an **abstract form class** with built-in whitelist validation ("accept known good"). It uses **language independent** validation rules (plain array) that can be reused for client-side validation (JavaScript) or passed to template rendering engines such as Twig or Smarty (HTML). The library can be used with **any framework** and **input source** (HTML, REST, RPC, ...).

A major advantage of this modular approach is that developers can use **unit testing** to instantly find bugs and **tune validation rules** without an existing HTML frontend or storage backend. It's perfectly suited to build **REST services**. Use case specific input value validation is also more secure than general model validation, which often relies on a blacklist ("reject known bad").

Besides basic validation rules such as type or length, more advanced rules are supported as well - for example **dependent fields** and **multi-page forms**. Validated values can be fetched individually, as flat array, by tag or by page.

The architecture is **simple by design**: Form classes can inherit their definitions from each other. If needed, the validation behavior can be changed using standard object-oriented methodologies. You don't need to hold a PhD in design patterns to understand how it works. The code is **mature** and **actively used** in several commercial and Open Source projects.

Example
-------

    $userForm = $container->get('form')->factory('User');

    $userForm->setDefinedWritableValues($_POST)->validate();
    
    if($userForm->hasErrors()) {
        throw new \Exception($userForm->getFirstError());
    }
    
    $userValues = $userForm->getValues();
    // ...

Form Validation vs Model Validation
-----------------------------------
The following visualization highlights the differences between client-side, input value (form) and model validation. Model validation generally operates on **trusted** data (internal system state) and should be **repeatable** at any point in time while input validation explicitly operates **once** on data that comes from **untrusted** sources (depending on the use case and user privileges). This separation makes it possible to build reusable models, controllers and forms that can be coupled through dependency injection (see REST controller example at the bottom).

Think of input validation as **whitelist** validation ("accept known good") and model validation as **blacklist** validation ("reject known bad"). Whitelist validation is more secure while blacklist validation prevents your model layer from being overly constraint to very specific use cases.

Invalid model data should always cause an **exception** to be thrown (otherwise the application can continue running without noticing the mistake) while invalid input values coming from external sources are **not** really unexpected, but rather common (think of a user registration form with the email or username already taken). Validation within a specific model may not be possible at all, if a set of input values must be validated together (because they depend on each other) but individual values are then stored in different models - at least it can create **additional dependencies** between models that would not be there otherwise up to the point that all models depend on each other. In short: The application may still work as expected, but the code is a mess.

From a theoretical standpoint, any complex system has more **internal state** than it exposes to the outside, thus it is never sufficient to use model validation only - except the model provides two sets of methods: some that are used internally and some that can be exposed to arbitrary input data from any source. Aside from side-effects such as limited user feedback (exception messages) and bloated model code, this approach may easily lead to serious security flaws. Malicious input data is a much higher threat to **multi-user** Web applications than to classical **single-user** desktop applications. Simple blacklist model validation may be fully sufficient for desktop applications, which are in full control of the user interface (view layer).

Client-side (JavaScript or HTML) form validation is always just a convenience feature and **not reliable**. However, with this library you can (at least partly) **reuse existing server-side form validation** rules to perform client-side validation, since they can be easily converted to JSON (for JavaScript) or be passed to template rendering engines such as Twig or Smarty (for HTML). Reusing model layer validation rules in a similar fashion is at least difficult, if not impossible.

See also: https://www.owasp.org/index.php/Data_Validation#Where_to_include_business_rule_validation

![Differences between client-side, input value (form) and model validation](https://www.lucidchart.com/publicSegments/view/5461f867-ae1c-44a4-b565-6f780a00cf27/image.png)

Form field properties
---------------------

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
hidden                 | User can not see the field
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

Example
-------
```
<?php

class UserForm extends \InputValidation\Form {
    protected function init(array $params = array())
    {
        $definition = array(
            'username': {
                type: 'string',
                caption: 'Username',
                required: true,
                min: 3,
                max: 15
            },
            'email': {
                type: 'email',
                caption: 'E-Mail',
                required: true
            },
            'gender': {
                type: 'string',
                caption: 'Gender',
                required: false,
                options: ['male', 'female'],
                optional: true
            },
            'birthday': {
                type: 'date',
                caption: 'Birthday',
                required: false
            },
            'password': {
                type: 'string',
                caption: 'Password',
                required: true,
                min: 5,
                max: 30
            },
            'password_again': {
                type: 'string',
                caption: 'Password confirmation',
                required: true,
                matches: 'password'
            },
            'continent': {
                type: 'string',
                caption: 'Region',
                required: true,
                options: ['north_america', 'south_america', 'europe', 'asia', 'australia']
            }
        );

        $this->setDefinition($definition);
    }
}
```

Public methods
--------------

**__construct(Translator $translator, Validator $validator, array $params = array())**

The constructor requires instances of Symfony\Component\Translation\TranslatorInterface, InputValidation\Validator and an optional set of arbitrary parameters, which are passed to **init(array $params = array())** (protected class method).

**factory($className, array $params = array())**

Creates a new form instance (with an optional set of arbitrary parameters for init() - see __construct()). $className must not include a prefix (e.g. namespace) and postfix (e.g. 'Form'), if $_factoryPrefix and $_factoryPostfix are set (protected class properties).

**setOptions(OptionsInterface $options)**

Sets an optional class instance to automatically fill option lists (see "options" form field property). OptionsInterface only requires a method get($listName) that returns an array of options.

**getOptions($listName = '')**

Returns the options list or instance (if parameter is empty)

**getTranslator()**

Returns the Translator instance (see __construct)

**setTranslator(Translator $translator)**

Sets the Translator instance (see __construct)

**getValidator()**

Returns the Validator instance (see __construct)

**setValidator(Validator $validator)**

Sets the Validator instance (see __construct)

**getLocale()**

Returns the current locale e.g. en, de or fr

**setLocale($locale)**

Sets the current locale e.g. en, de or fr

**setDefinition(array $definition)**

Sets the form field definition array (see example and form field properties)

**getDefinition($key = null, $propertyName = null)**

Returns the form field definition(s). If $key is null, definitions for all fields are returned. If $propertyName is null and $key is not null, only the definition of the given field key are returned. If both arguments are not null, only the definition of the given form field property is returned (for example, getDefinition('firstname', 'type') returns 'string'). A FormException is thrown otherwise.

**addDefinition($key, array $definition)**

Adds a single form field definition (see form field properties)

**changeDefinition($key, array $changes)**

Changes a single form field definition (see form field properties)

**getAsGroupedArray()**

Returns grouped form field definitions and values (you must use setGroups() first)

**setGroups(array $groups)**

Sets form field groups (optional feature, if you want to reuse your form definition to reder the form as HTML).

Example:
```
$form->setGroups(
  array(
    'first_group' => array('field1', 'field2'),
    'second_group' => array('field3')
  )
);
```

**getAsArray()**

Returns the complete form (definition + values) as array, which can be used in the view templates to render the form or be converted to JSON/XML for Web services (REST/RPC).

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

**hasErrors()**

Returns true, if the form has errors

**isValid()**

eturns true, if the form is valid (has no errors)

**getErrors()**

Returns all errors and throws an exception, if the validation was not performed yet (you must call validate() before calling getErrors()).

**getFirstError()**

Returns the first error as string

**getErrorsAsText()**

Returns all errors as indented text (for command line applications)

**getErrorsByPage()**

Returns all errors grouped by page and throws an exception, if the validation was not performed yet (you must call validate() before calling getErrorsByPage()).

**clearErrors()**

Resets the validation and clears all errors

**getHash()**

Returns hash that uniquely identifies the form (for caching comprehensive forms)

Validation in REST controller action context
--------------------------------------------

This example shows how to validate user input in a REST controller action (note that HTTP doesn't support multiple error messages by default and that form initialization using existing model values is optional):

```
class UserController
{
    protected $user;
    protected $form;

    public function __construct(User $user, UserForm $form)
    {
        $this->user = $user;
        $this->form = $form;
    }
    
    public function putAction($id, Request $request) // Update
    {
        $this->user->find($id); // Find entity (throws exception, if not found)
        
        $this->form->setDefinedValues($this->user->getValues()); // Initialization
        
        $this->form->setDefinedWritableValues($request->request->all()); // Input values
        
        $this->form->validate(); // Validation

        if($this->form->isValid()) {
            $this->user->update($this->form->getValues()); // Update values
        } else {
            // Return first error, since HTTP isn't designed to return multiple errors at once
            throw new FormInvalidException($this->form->getFirstError());
        }

        return $this->user->getValues(); // Return updated entity values
    }
}
```

See also
--------
* [Doctrine ActiveRecord - Object-oriented CRUD for Doctrine DBAL](https://github.com/lastzero/doctrine-active-record)
