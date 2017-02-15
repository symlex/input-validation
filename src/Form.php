<?php

namespace InputValidation;

use DateTime;
use Symfony\Component\Translation\TranslatorInterface as Translator;
use InputValidation\Exception\FormException as Exception;

/**
 * \InputValidation\Form can be used to validate user input of any origin (POST data, CLI or SOAP/REST)
 *
 * The form classes return localized validation messages and pass on the form definition
 * to controllers that render the forms to HTML using view templates and interact with
 * models.
 *
 * A major advantage of this modular approach is that developers can use unit testing to instantly
 * find bugs and tune the validation rules without the need for an HTML front-end and manual user
 * input.
 *
 * Form classes can inherit their definitions from each other. If needed, the validation behavior
 * can be changed using standard object-oriented methodologies (e.g. overwriting or extending
 * the parent methods).
 *
 * =============================| Description of form definition parameters |===========================================
 * caption              Field title (used for form rendering and in validation messages)
 * type                 Data type: int, numeric, scalar, list, bool, string, email, ip, url, date, datetime, time and switch
 * type_params          Optional parameters for data type validation
 * options              Array of possible values for the field (for select lists or radio button groups)
 * min                  Minimum value for numbers/dates, length for strings or number of elements for lists
 * max                  Maximum value for numbers/dates, length for strings or number of elements for lists
 * required             Field cannot be empty
 * readonly             User is not allowed to change the field
 * hidden               User can not see the field
 * default              Default value
 * optional             A checkbox-like form input element is used (the form class will assign false for
 *                      boolean fields or array() for lists, if the value is not passed to setDefinedValues()
 *                      or setDefinedWritableValues()). This is a work around, because browsers do not submit
 *                      any data for unchecked checkboxes or multi-select fields without a selected element.
 * regex                Regular expression to match against
 * matches              Field value must match another form field (e.g. for password or email validation).
 *                      Property can be prefixed with "!" to state that the fields must be different.
 * depends              Field is required, if the given form field is not empty
 * depends_value        Field is required, if the field defined in "depends" has this value
 * depends_value_empty  Field is required, if the field defined in "depends" is empty
 * depends_first_option Field is required, if the field defined in "depends" has the first value (see "options")
 * depends_last_option  Field is required, if the field defined in "depends" has the last value (see "options")
 * page                 Page number for multi-page forms
 * tags                 List of tags, e.g. ['user', 'profile'] - can be used to get values by tag (optional)
 * =====================================================================================================================
 *
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class Form
{
    /**
     * The form definition array (see documentation above)
     *
     * @var array
     */
    private $_definition = array();

    /**
     * Form field values
     *
     * @var array
     */
    private $_values = array();

    /**
     * Contains form field errors after calling validate()
     *
     * @var array
     */
    private $_errors = array();

    /**
     * Used to group form fields (optional feature)
     *
     * @var array
     */
    private $_groups = array();

    /**
     * Reference to object that implements Form\OptionsInterface (for form elements that contain lists)
     *
     * @var Form\OptionsInterface
     */
    private $_options = null;

    /**
     * @var Translator
     */
    protected $_translator = null;

    /**
     * @var Form\Validator
     */
    protected $_validator = null;

    /**
     * Optional form initialization parameters passed to init()
     *
     * @var array
     */
    protected $_params = array();

    /**
     * True, after the validation was executed
     *
     * @var bool
     */
    protected $_validationDone = false;

    /**
     * @var string
     */
    protected $_defaultOptionLabel = 'form.please_select';

    /**
     * Form constructor.
     *
     * @param Translator $translator
     * @param Form\Validator $validator
     * @param Form\OptionsInterface $options
     * @param array $params
     */
    public function __construct(Translator $translator, Form\Validator $validator, Form\OptionsInterface $options, array $params = array())
    {
        $this->setTranslator($translator);
        $this->setValidator($validator);
        $this->setOptions($options);
        $this->setParams($params);
        $this->init($params);
    }

    /**
     * Sets form config parameters
     *
     * @param array $params
     */
    protected function setParams(array $params)
    {
        $this->_params = $params;
    }

    /**
     * Returns optional config parameter or throws Exception, if parameter does not exist
     *
     * @param string $name
     * @throws Exception
     * @return mixed
     */
    protected function getParam(string $name)
    {
        if (!array_key_exists($name, $this->_params)) {
            throw new Exception ('Required parameter "' . $name . '" was not set');
        }

        return $this->_params[$name];
    }

    /**
     * Form initialization method - must be implemented by inherited form classes
     *
     * @param array $params Optional parameters
     */
    protected function init(array $params = array())
    {
        // must be implemented by inherited form classes
    }

    /**
     * @param Form\OptionsInterface $options
     * @return $this
     */
    public function setOptions(Form\OptionsInterface $options)
    {
        $this->_options = $options;

        return $this;
    }

    /**
     * @throws Exception
     * @return Form\OptionsInterface
     */
    public function getOptions(): Form\OptionsInterface
    {
        if (empty($this->_options)) {
            throw new Exception ('No options instance set');
        }

        return $this->_options;
    }

    /**
     * Returns a list of options e.g. countries
     *
     * 'country' => array(
     *     'type' => 'string',
     *     'default' => 'DE',
     *     'options' => $this->form->options('countries')
     * )
     *
     * @param string $listName Name of options list
     * @return array
     */
    public function options(string $listName): array
    {
        $result = $this->getOptions()->get($listName);

        return $result;
    }

    /**
     * Returns a list of options with default label for no selection
     *
     * 'country' => array(
     *     'type' => 'string',
     *     'required' => true,
     *     'options' => $this->form->optionsWithDefault('countries')
     * )
     *
     * @param string $listName Optional name of options list (shortcut)
     * @param string $defaultLabel The field label translation token
     * @return array
     */
    public function optionsWithDefault(string $listName, string $defaultLabel = ''): array
    {
        $result = $this->getDefaultOption($defaultLabel) + $this->options($listName);

        return $result;
    }

    /**
     * Sets the default option label - see getDefaultOption()
     *
     * @param string $defaultLabel The field label translation token
     * @return $this
     */
    public function setDefaultOptionLabel(string $defaultLabel)
    {
        $this->_defaultOptionLabel = $defaultLabel;

        return $this;
    }

    /**
     * Returns the default option label - see getDefaultOption()
     *
     * @return string
     */
    public function getDefaultOptionLabel(): string
    {
        return $this->_defaultOptionLabel;
    }

    /**
     * Helper function to return default option string like "Please select"
     *
     * @param string $label The field label token
     * @return array
     */
    protected function getDefaultOption(string $label = ''): array
    {
        if ($label == '') {
            $label = $this->getDefaultOptionLabel();
        }

        return array('' => $this->translate($label));
    }

    /**
     * Set form field group definition array
     *
     * Example:
     *
     * $this->setGroups(
     *      array(
     *          'first_group' => array('field1', 'field2'),
     *          'second_group' => array('field3')
     *      )
     *  );
     *
     * @param array $groups The group definition array
     * @return $this
     */
    public function setGroups(array $groups)
    {
        $this->_groups = $groups;

        return $this;
    }

    /**
     * @return Translator
     * @throws Exception
     */
    public function getTranslator(): Translator
    {
        if (!$this->_translator) {
            throw new Exception('Translator was not set');
        }

        return $this->_translator;
    }

    /**
     * @param Translator $translator
     * @return $this
     */
    public function setTranslator(Translator $translator)
    {
        $this->_translator = $translator;

        return $this;
    }

    /**
     * @return Form\Validator
     * @throws Exception
     */
    public function getValidator(): Form\Validator
    {
        if (!$this->_validator) {
            throw new Exception('Validator was not set');
        }

        return $this->_validator;
    }

    /**
     * @param Form\Validator $validator
     * @return $this
     */
    public function setValidator(Form\Validator $validator)
    {
        $validator->setForm($this);

        $this->_validator = $validator;

        return $this;
    }

    /**
     * Sets the current locale e.g. en, de or fr
     *
     * @param string $locale
     * @return $this
     */
    public function setLocale(string $locale)
    {
        $this->getTranslator()->setLocale($locale);

        return $this;
    }

    /**
     * Returns the current locale string
     *
     * @return string
     */
    public function getLocale(): string
    {
        return $this->getTranslator()->getLocale();
    }

    /**
     * Sets the form field definition array (see documentation)
     *
     * @param array $definition The form field definition array
     * @return $this
     */
    public function setDefinition(array $definition)
    {
        $this->_definition = $definition;

        return $this;
    }

    /**
     * Returns the form field definition(s)
     *
     * @param string $name Optional field name (only the single field definition is returned)
     * @param string $property Optional field property name (only the property value is returned)
     * @throws Exception
     * @return mixed
     */
    public function getDefinition(string $name = null, string $property = null)
    {
        if (!is_array($this->_definition)) {
            throw new Exception('Form definition is not an array. Something went totally wrong.');
        } elseif (count($this->_definition) == 0) {
            throw new Exception('Form definition is empty.');
        } elseif ($name === null) {
            return $this->_definition;
        } elseif (isset($this->_definition[$name])) {
            if ($property != null) {
                if (isset($this->_definition[$name][$property])) {
                    return $this->_definition[$name][$property];
                }

                return null;
            }

            return $this->_definition[$name];
        }

        throw new Exception('No form field definition found for "' . $name . '".');
    }

    /**
     * Returns form field definition as array
     *
     * @param string $name
     * @return array
     */
    public function getFieldDefinition(string $name): array
    {
        return $this->getDefinition($name);
    }

    /**
     * Returns form field property definition
     *
     * @param string $name
     * @param string $property
     * @return mixed
     */
    public function getFieldPropertyDefinition(string $name, string $property)
    {
        return $this->getDefinition($name, $property);
    }

    /**
     * Adds a single form field definition
     *
     * @param string $name Field name
     * @param array $definition Field definition array
     * @throws Exception
     * @return $this
     */
    public function addDefinition(string $name, array $definition)
    {
        if (isset($this->_definition[$name])) {
            throw new Exception('Definition for "' . $name . '" already exists');
        }

        $this->_definition[$name] = $definition;

        return $this;
    }

    /**
     * Changes a single form field definition
     *
     * @param string $name Field name
     * @param array $changes Field definition array
     * @throws Exception
     * @return $this
     */
    public function changeDefinition(string $name, array $changes)
    {
        if (!isset($this->_definition[$name])) {
            throw new Exception('Definition for "' . $name . '" does not exist');
        }

        foreach ($changes as $prop => $val) {
            if ($val === null) {
                unset($this->_definition[$name][$prop]);
            } else {
                $this->_definition[$name][$prop] = $val;
            }
        }

        return $this;
    }

    /**
     * Returns field definition and value as JSON compatible array
     *
     * @param string $name
     * @return array
     */
    public function getFieldAsArray(string $name): array
    {
        $result = $this->getFieldDefinition($name);

        $result['name'] = $name;

        if (empty($result['caption'])) {
            $result['caption'] = $this->_($result['name']);
        }

        $value = $this->$name;
        $type = @$result['type'];

        if (($type == 'date' || $type == 'datetime' || $type == 'time') && is_object($value)) {
            $value = $value->format($this->translate('form.' . $type));
        }

        $result['value'] = $value;
        $result['uid'] = 'id' . uniqid();

        if (isset($result['options']) && is_array($result['options'])) {
            $options = array();

            foreach ($result['options'] as $option => $label) {
                $options[] = array('option' => $option, 'label' => $label);
            }

            $result['options'] = $options;
        }

        return $result;
    }

    /**
     * Returns the complete form (definition and all values) as JSON compatible array,
     * which can be used to render the form in templates
     *
     * @return array
     */
    public function getAsArray(): array
    {
        $result = array();

        foreach ($this->_definition as $key => $def) {
            $result[] = $this->getFieldAsArray($key);
        }

        return $result;
    }

    /**
     * Returns form fields structured in groups (you must use setGroups() first)
     *
     * @return array
     */
    public function getAsGroupedArray(): array
    {
        $result = array();

        foreach ($this->_groups as $groupName => $memberKeys) {
            $members = array();

            foreach ($memberKeys as $key) {
                $members[] = $this->getFieldAsArray($key);
            }

            $result[] = array(
                'group_name' => $groupName,
                'group_caption' => $this->_('group_' . $groupName),
                'fields' => $members
            );
        }

        return $result;
    }

    /**
     * Returns true, if the field is writable by the user (readonly property)
     *
     * @param string $name
     * @return bool
     */
    protected function isWritable(string $name): bool
    {
        return $this->getFieldPropertyDefinition($name, 'readonly') != true;
    }

    /**
     * Returns true, if the field is optional
     *
     * This is a workaround for Web form elements, that do not get submitted, if empty.
     * In general, this applies to non-checked checkboxes or to empty form elements
     * submitted by certain JavaScript frameworks (e.g. AngularJS).
     *
     * @param string $name
     * @return bool
     */
    protected function isOptional(string $name): bool
    {
        return ($this->getFieldPropertyDefinition($name, 'checkbox') == true) || ($this->getFieldPropertyDefinition($name, 'optional') == true);
    }

    /**
     * Sets the default types for values of form fields marked as optional
     *
     * @param string $name The field name
     * @param array $values Reference to the array containing all form values
     * @return $this
     */
    protected function setOptionalValueInArray(string $name, &$values)
    {
        if ($this->isOptional($name) && !array_key_exists($name, $values)) {
            $default = $this->getFieldPropertyDefinition($name, 'default');

            if (is_null($default)) {
                $type = $this->getFieldPropertyDefinition($name, 'type');

                switch ($type) {
                    case 'list':
                        $default = array();
                        break;
                    case 'bool':
                        $default = false;
                        break;
                }
            }

            $values[$name] = $default;
        }

        return $this;
    }

    /**
     * Sets all form values (does not check, if the fields exist or if the fields are writable by the user)
     * Note: Throws an exception, if you try to set values for undefined fields
     *
     * @param array $values The values (key must be the field name)
     * @return $this
     */
    public function setAllValues(array $values)
    {
        foreach ($values as $key => $value) {
            $this->$key = $value;
        }

        return $this;
    }

    /**
     * Iterates through the form definition and sets the values for fields, that are present in the form definition
     *
     * @param array $values The values (key must be the field name)
     * @throws Exception
     * @return $this
     */
    public function setDefinedValues(array $values)
    {
        foreach ($this->_definition as $key => $value) {
            $this->setOptionalValueInArray($key, $values);

            if (!array_key_exists($key, $values)) {
                throw new Exception ('Value is missing for "' . $key . '"');
            }

            $this->$key = $values[$key];
        }

        return $this;
    }

    /**
     * Iterates through the passed value array and sets the values for fields, that are writable by the user
     *
     * @param array $values The values (key must be the field name)
     * @return $this
     */
    public function setWritableValues(array $values)
    {
        foreach ($values as $key => $value) {
            if ($this->isWritable($key)) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    /**
     * Sets the values for fields, that are present in the form definition
     * and that are writable by the user (recommended method for most use cases)
     *
     * @param array $values The values (key must be the field name)
     * @throws Exception
     * @return $this
     */
    public function setDefinedWritableValues(array $values)
    {
        foreach ($this->_definition as $key => $value) {
            if ($this->isWritable($key)) {
                $this->setOptionalValueInArray($key, $values);

                if (!array_key_exists($key, $values)) {
                    throw new Exception ('Value is missing for "' . $key . '"');
                }


                $this->$key = $values[$key];
            }
        }

        return $this;
    }

    /**
     * Sets the values for fields on the given page, that are present in the form definition
     * and that are writable by the user (recommended method for most use cases)
     *
     * @param array $values The values (key must be the field name)
     * @param string $page
     * @throws Exception
     * @return $this
     */
    public function setWritableValuesOnPage(array $values, string $page)
    {
        foreach ($this->_definition as $key => $value) {
            if (isset($value['page']) && $value['page'] == $page && $this->isWritable($key)) {
                $this->setOptionalValueInArray($key, $values);

                if (!array_key_exists($key, $values)) {
                    throw new Exception ('Value is missing for "' . $key . '"');
                }

                $this->$key = $values[$key];
            }
        }

        return $this;
    }

    /**
     * Returns the form values for all elements grouped by page
     *
     * @return array
     */
    public function getValuesByPage(): array
    {
        $result = array();

        foreach ($this->_definition as $key => $value) {
            $page = $this->getFieldPropertyDefinition($key, 'page');

            if ($page) {
                $result[$page][$key] = $this->$key;
            }
        }

        return $result;
    }

    /**
     * Returns the form values for all elements by tag
     *
     * @return array
     */
    public function getValuesByTag(string $tag): array
    {
        $result = array();

        foreach ($this->_definition as $key => $value) {
            $tags = $this->getFieldPropertyDefinition($key, 'tags');

            if (is_array($tags) && in_array($tag, $tags)) {
                $result[$key] = $this->$key;
            }
        }

        return $result;
    }

    /**
     * Returns all form field values
     *
     * @return array
     */
    public function getValues(): array
    {
        $result = array();

        foreach ($this->_definition as $key => $value) {
            $result[$key] = $this->$key;
        }

        return $result;
    }

    /**
     * Returns all writable form field values
     *
     * @return array
     */
    public function getWritableValues(): array
    {
        $result = array();

        foreach ($this->_definition as $key => $value) {
            if ($this->isWritable($key)) {
                $result[$key] = $this->$key;
            }
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function convertValueToBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_array($value)) {
            return count($value) > 0;
        }

        $value = (string)$value;

        switch ($value) {
            case '1':
            case 'yes':
            case 'true':
                return true;
            case '0':
            case 'no':
            case 'false':
                return false;
            default:
                return (bool)$value;
        }
    }

    /**
     * Magic setter
     */
    public function __set(string $name, $value)
    {
        if (isset($this->_definition[$name])) {
            $type = $this->getFieldPropertyDefinition($name, 'type');
            if ($type == 'list' && $value == array('')) {
                $value = array();
            }

            if ($type == 'bool') {
                $value = $this->convertValueToBool($value);
            }

            if (($type == 'date' || $type == 'datetime' || $type == 'time') && !empty($value) && !is_object($value)) {
                if ($date = DateTime::createFromFormat($this->translate('form.' . $type), $value)) {
                    $value = $date;
                }
            }

            if ($type != 'string' && $value === '') {
                $value = null;
            }

            $this->clearErrors();

            $this->_values[$name] = $value;
        } else {
            throw new Exception ('No form field defined for "' . $name . '"');
        }
    }

    /**
     * Magic getter
     *
     * @param string $name
     * @throws Exception
     * @return mixed
     */
    public function __get(string $name)
    {
        try {
            $default = $this->getFieldPropertyDefinition($name, 'default');

            if (array_key_exists($name, $this->_values)) {
                return $this->_values[$name];
            }

            return $default;
        } catch (Exception $e) {
            throw new Exception ('No form field defined for "' . $name . '"');
        }
    }

    /**
     * Magic isset()
     *
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        try {
            $value = $this->__get($name);

            $result = ($value !== null);
        } catch (Exception $e) {
            $result = false;
        }

        return $result;
    }

    /**
     * Uses the Translator adapter to translate a string/token
     *
     * @param string $token The string token
     * @param array $params
     * @return string The translated string
     */
    public function translate(string $token, array $params = array()): string
    {
        return $this->getTranslator()->trans($token, $params);
    }


    /**
     * Alias for translate()
     *
     * @param string $token The string token
     * @param array $params
     * @return string The translated string
     */
    public function _(string $token, array $params = array())
    {
        return $this->translate($token, $params);
    }

    /**
     * Returns a translated field caption
     *
     * @param string $name The field name
     * @return string
     */
    public function getFieldCaption(string $name): string
    {
        $caption = $this->getFieldPropertyDefinition($name, 'caption');

        if ($caption) {
            return $this->translate($caption);
        }

        return $name;
    }

    /**
     * Adds a validation error
     * Note: Accepts unlimited parameters for sprintf() replacements in the translated error message
     *
     * @param string $name The field name
     * @param string $token Error message token
     * @param array $params Error message replacements
     * @return $this
     */
    public function addError(string $name, string $token, array $params = array())
    {
        $caption = $this->getFieldCaption($name);

        $this->_errors[$name][] = $this->translate($token, $params + array('%field%' => $caption));

        return $this;
    }

    /**
     * Validates the form
     *
     * Note: You can use getErrors(), getErrorsByPage() and hasErrors() to get the validation results
     */
    public function validate()
    {
        if ($this->_validationDone) {
            throw new Exception('Validation was already done - call clearErrors() to reset');
        }

        foreach ($this->_definition as $key => $def) {
            if (is_int($key)) {
                throw new Exception ('Form field names can not be numeric - there probably is a typo in the form definition');
            }

            $value = $this->$key;

            $this->getValidator()->validateField($key, $def, $value);
        }

        $this->_validationDone = true;

        return $this;
    }

    /**
     * Returns true, if the form has errors
     *
     * @return bool
     */
    public function hasErrors(): bool
    {
        return (count($this->getErrors()) > 0);
    }

    /**
     * Returns true, if the form is valid (has no errors)
     *
     * @return bool
     */
    public function isValid(): bool
    {
        return !$this->hasErrors();
    }

    /**
     * Returns all errors and throws an exception, if the validation was not done yet
     * Note: You must call validate() before getErrors()
     *
     * @throws Exception
     * @return array
     */
    public function getErrors(): array
    {
        if (!$this->_validationDone) {
            throw new Exception('You must run validate() before calling getErrors()');
        }

        return $this->_errors;
    }

    /**
     * Returns the first error as string
     *
     * @return string
     */
    public function getFirstError(): string
    {
        $result = '';
        $errors = $this->getErrors();
        $firstField = reset($errors);

        if ($firstField && isset($firstField[0])) {
            $result = $firstField[0];
        }

        return $result;
    }

    /**
     * Returns all errors as indented text
     *
     * @return string
     */
    public function getErrorsAsText(): string
    {
        $result = '';
        $fieldCounter = 0;
        $fields = $this->getErrors();

        foreach ($fields as $fieldName => $fieldErrors) {
            $fieldCounter++;
            $result .= "$fieldCounter) $fieldName\n";

            foreach ($fieldErrors as $error) {
                $result .= "   - $error\n";
            }
        }

        return $result;
    }

    /**
     * Returns all errors grouped by page and throws an exception, if the validation was not done yet
     * Note: You must call validate() before getErrorsByPage()
     *
     * @return array
     */
    public function getErrorsByPage(): array
    {
        $result = array();
        $errors = $this->getErrors();

        foreach ($errors as $name => $val) {
            $page = $this->getFieldPropertyDefinition($name, 'page');

            if ($page) {
                $result[$page][$name] = $val;
            }
        }

        return $result;
    }

    /**
     * Resets the validation and clears all errors
     */
    public function clearErrors()
    {
        $this->_validationDone = false;

        if (count($this->_errors) != 0) {
            $this->_errors = array();
        }

        return $this;
    }

    /**
     * Returns unique form hash to uniquely identify the form
     *
     * @return string
     */
    public function getHash(): string
    {
        return md5(serialize($this->getDefinition()));
    }
}
