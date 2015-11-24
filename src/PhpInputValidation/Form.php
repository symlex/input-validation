<?php

namespace PhpInputValidation;

use Symfony\Component\Translation\TranslatorInterface as Translator;
use DateTime;
use PhpInputValidation\Exception\FormException as Exception;

/**
 * \PhpInputValidation\Form can be used to validate user input of any origin (POST data, CLI or SOAP/REST)
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
 * =====================================================================================================================
 *
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class Form
{
    private $_definition = array(); // The form definition array (see documentation above)
    private $_values = array(); // The form values
    private $_errors = array(); // The form errors (after calling validate())
    private $_groups = array(); // Optional form element groups
    private $_options = null;

    protected $_factoryPrefix = '';
    protected $_factoryPostfix = '';
    protected $_validationDone = false; // Set to true, if the validation was executed

    protected $_translator = null;
    protected $_validator = null;

    protected $_params = array(); // Optional params for init()

    public function __construct(Translator $translator, Validator $validator, array $params = array())
    {
        $this->setTranslator($translator);
        $this->setValidator($validator);
        $this->init($params);
    }

    /**
     * Returns optional config parameter or throws Exception, if parameter does not exist
     *
     * @param string $name
     * @throws Exception
     * @return string
     */
    protected function getParam($name)
    {
        if (isset($this->_params[$name])) {
            throw new Exception ('Required parameter "' . $name . '" was not set');
        }

        return $this->_params[$name];
    }

    /**
     * Creates a new form instance
     *
     * @param $className string Form class name
     * @param $params array Optional config parameters for init()
     * @return Form
     */
    public function factory($className, array $params = array())
    {
        $className = $this->_factoryPrefix . '\\' . $className . $this->_factoryPostfix;

        $result = new $className ($this->getTranslator(), $this->getValidator(), $params);

        return $result;
    }

    public function setFactoryPrefix($prefix)
    {
        $this->_factoryPrefix = $prefix;
    }

    public function setFactoryPostfix($postfix)
    {
        $this->_factoryPostfix = $postfix;
    }

    /**
     * Init function (don't overwrite the constructor)
     *
     * @param array $params Optional parameters
     */
    protected function init(array $params = array())
    {
        // must be implemented by inherited form classes
    }

    /**
     * @param OptionsInterface $options
     * @return $this
     */
    public function setOptions(OptionsInterface $options)
    {
        $this->_options = $options;

        return $this;
    }

    /**
     * Returns the options list or instance (if parameter is empty)
     *
     * @param string $listName Optional name of options list (shortcut)
     * @throws Exception
     * @return OptionsInterface
     */
    public function getOptions($listName = '')
    {
        if (empty($this->_options)) {
            throw new Exception ('No options instance set');
        }

        if ($listName == '') {
            $result = $this->_options;
        } else {
            $result = $this->_options->get($listName);
        }

        return $result;
    }

    /**
     * Helper function to return default option string like "Please select"
     *
     * @param string $label The field label token
     * @return array
     */
    protected function defaultOption($label = 'please_select')
    {
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
     * @param $groups array The group definition array
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
    public function getTranslator()
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
     * @return Validator
     * @throws Exception
     */
    public function getValidator()
    {
        if (!$this->_validator) {
            throw new Exception('Validator was not set');
        }

        return $this->_validator;
    }

    /**
     * @param Validator $validator
     * @return $this
     */
    public function setValidator(Validator $validator)
    {
        $validator->setForm($this);

        $this->_validator = $validator;

        return $this;
    }

    /**
     * Sets the current locale e.g. en, de or fr
     */
    public function setLocale($locale)
    {
        $this->getTranslator()->setLocale($locale);

        return $this;
    }

    /**
     * Returns the current locale string
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->getTranslator()->getLocale();
    }

    /**
     * Sets the form field definition array (see documentation)
     *
     * @param $definition array The form field definition array
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
     * @param $key string Optional field name (only the single field definition is returned)
     * @param $propertyName string Optional field property name (only the property value is returned)
     * @throws Exception
     * @return mixed
     */
    public function getDefinition($key = null, $propertyName = null)
    {
        if (!is_array($this->_definition)) {
            throw new Exception('Form definition is not an array. Something went totally wrong.');
        } elseif (count($this->_definition) == 0) {
            throw new Exception('Form definition is empty.');
        } elseif ($key == null) {
            return $this->_definition;
        } elseif (isset($this->_definition[$key])) {
            if ($propertyName != null) {
                if (isset($this->_definition[$key][$propertyName])) {
                    return $this->_definition[$key][$propertyName];
                }

                return null;
            }

            return $this->_definition[$key];
        }

        throw new Exception('No form field definition found for "' . $key . '".');
    }

    /**
     * Adds a single form field definition
     *
     * @param $key string Field name
     * @param $definition array Field definition array
     * @throws Exception
     * @return $this
     */
    public function addDefinition($key, array $definition)
    {
        if (isset($this->_definition[$key])) {
            throw new Exception('Definition for ' . $key . ' already exists');
        }

        $this->_definition[$key] = $definition;

        return $this;
    }

    /**
     * Changes a single form field definition
     *
     * @param $key string Field name
     * @param $changes array Field definition array
     * @throws Exception
     * @return $this
     */
    public function changeDefinition($key, array $changes)
    {
        if (!isset($this->_definition[$key])) {
            throw new Exception('Definition for ' . $key . ' does not exist');
        }

        foreach ($changes as $prop => $val) {
            if ($val === null) {
                unset($this->_definition[$key][$prop]);
            } else {
                $this->_definition[$key][$prop] = $val;
            }
        }

        return $this;
    }

    /**
     * Returns the complete form (definition + values) as array, which can
     * be used in the view templates to render the form or be converted to
     * JSON/XML for Web services
     *
     * @return array
     */
    public function getAsArray()
    {
        $result = array();

        foreach ($this->_definition as $key => $def) {
            $result[$key] = $def;
            $value = $this->$key;
            $type = $def['type'];

            if (($type == 'date' || $type == 'datetime' || $type == 'time') && is_object($value)) {
                $value = $value->format($this->translate('form.' . $type));
            }

            $result[$key]['value'] = $value;
            $result[$key]['uid'] = 'id' . uniqid();
        }

        return $result;
    }


    /**
     * Returns form fields structured in groups (you must use setGroups() first)
     *
     * @return array
     */
    public function getAsGroupedArray()
    {
        $form = $this->getAsArray();
        $result = array();

        foreach ($this->_groups as $groupName => $memberKeys) {
            $members = array();

            foreach ($memberKeys as $key) {
                $members[$key] = $form[$key];
            }

            $result[$this->_('group_' . $groupName)] = $members;
        }

        return $result;
    }

    /**
     * Returns true, if the field is writable by the user (readonly property)
     *
     * @param $key string
     * @return bool
     */
    protected function isWritable($key)
    {
        return $this->getDefinition($key, 'readonly') != true;
    }

    /**
     * Returns true, if the field is optional
     *
     * This is a workaround for Web form elements, that do not get submitted, if empty.
     * In general, this applies to non-checked checkboxes or to empty form elements
     * submitted by certain JavaScript frameworks (e.g. AngularJS).
     *
     * @param $key string
     * @return bool
     */
    protected function isOptional($key)
    {
        return ($this->getDefinition($key, 'checkbox') == true) || ($this->getDefinition($key, 'optional') == true);
    }

    /**
     * Sets the default types for values of form fields marked as optional
     *
     * @param $key string The field name
     * @param $values array Reference to the array containing all form values
     * @return $this
     */
    protected function setOptionalValueInArray($key, &$values)
    {
        if ($this->isOptional($key) && !array_key_exists($key, $values)) {
            $type = $this->getDefinition($key, 'type');
            switch ($type) {
                case 'list':
                    $values[$key] = array();
                    break;
                case 'bool':
                    $values[$key] = false;
                    break;
                default:
                    $values[$key] = null;
            }
        }

        return $this;
    }

    /**
     * Sets all form values (does not check, if the fields exist or if the fields are writable by the user)
     * Note: Throws an exception, if you try to set values for undefined fields
     *
     * @param $values array The values (key must be the field name)
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
     * @param $values array The values (key must be the field name)
     * @throws Exception
     * @return $this
     */
    public function setDefinedValues(array $values)
    {
        foreach ($this->_definition as $key => $value) {
            $this->setOptionalValueInArray($key, $values);

            if (!array_key_exists($key, $values)) {
                throw new Exception ('Array provided to setDefinedValues() was not complete: ' . $key);
            }

            $this->$key = $values[$key];
        }

        return $this;
    }

    /**
     * Iterates through the passed value array and sets the values for fields, that are writable by the user
     *
     * @param $values array The values (key must be the field name)
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
     * @param $values array The values (key must be the field name)
     * @throws Exception
     * @return $this
     */
    public function setDefinedWritableValues(array $values)
    {
        foreach ($this->_definition as $key => $value) {
            if ($this->isWritable($key)) {
                $this->setOptionalValueInArray($key, $values);

                if (!array_key_exists($key, $values)) {
                    throw new Exception ('Array provided to setDefinedWritableValues() was not complete: ' . $key);
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
     * @param $values array The values (key must be the field name)
     * @param $page scalar
     * @throws Exception
     * @return $this
     */
    public function setWritableValuesOnPage(array $values, $page)
    {
        foreach ($this->_definition as $key => $value) {
            if (isset($value['page']) && $value['page'] == $page && $this->isWritable($key)) {
                $this->setOptionalValueInArray($key, $values);

                if (!array_key_exists($key, $values)) {
                    throw new Exception ('Array provided to setWritableValuesOnPage() was not complete: ' . $key);
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
    public function getValuesByPage()
    {
        $result = array();

        foreach ($this->_definition as $key => $value) {
            $page = $this->getDefinition($key, 'page');

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
    public function getValuesByTag($tag)
    {
        $result = array();

        foreach ($this->_definition as $key => $value) {
            $tags = $this->getDefinition($key, 'tags');

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
    public function getValues()
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
    public function getWritableValues()
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
    protected function convertValueToBool($value)
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
    public function __set($key, $val)
    {
        if (isset($this->_definition[$key])) {
            $type = $this->getDefinition($key, 'type');
            if ($type == 'list' && $val == array('')) {
                $val = array();
            }

            if ($type == 'bool') {
                $val = $this->convertValueToBool($val);
            }

            if (($type == 'date' || $type == 'datetime' || $type == 'time') && !empty($val) && !is_object($val)) {
                if ($date = DateTime::createFromFormat($this->translate('form.' . $type), $val)) {
                    $val = $date;
                }
            }

            if ($type != 'string' && $val === '') {
                $val = null;
            }

            $this->clearErrors();

            $this->_values[$key] = $val;
        } else {
            throw new Exception ('Form field not defined: ' . $key);
        }
    }

    /**
     * Magic getter
     */
    public function __get($key)
    {
        try {
            $default = $this->getDefinition($key, 'default');

            if (array_key_exists($key, $this->_values)) {
                return $this->_values[$key];
            }

            return $default;
        } catch (Exception $e) {
            throw new Exception ('Form field not defined: ' . $key);
        }
    }

    /**
     * Uses the Translator adapter to translate a string/token
     *
     * @param string $token The string token
     * @param array $params
     * @return string The translated string
     */
    public function translate($token, array $params = array())
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
    public function _($token, array $params = array())
    {
        return $this->translate($token, $params);
    }


    /**
     * Returns a translated field caption
     *
     * @param $key string The field name
     * @return string
     */
    protected function getFieldCaption($key)
    {
        $caption = $this->getDefinition($key, 'caption');

        if ($caption) {
            return $this->translate($caption);
        }

        return $key;
    }

    /**
     * Adds a validation error
     * Note: Accepts unlimited parameters for sprintf replacements in the translated error message
     *
     * @param string $key The field name
     * @param string $token Error message token
     * @param array $params Error message replacements
     * @return $this
     */
    public function addError($key, $token, array $params = array())
    {
        $caption = $this->getFieldCaption($key);

        $this->_errors[$key][] = $this->translate($token, $params + array('%field%' => $caption));

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
            throw new Exception('Validation was already done. Call clearErrors() to reset');
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
    public function hasErrors()
    {
        return (count($this->getErrors()) > 0);
    }

    /**
     * Returns true, if the form is valid (has no errors)
     *
     * @return bool
     */
    public function isValid()
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
    public function getErrors()
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
    public function getFirstError()
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
    public function getErrorsAsText()
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
    public function getErrorsByPage()
    {
        $result = array();
        $errors = $this->getErrors();

        foreach ($errors as $key => $val) {
            $page = $this->getDefinition($key, 'page');

            if ($page) {
                $result[$page][$key] = $val;
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
    public function getHash()
    {
        return md5(serialize($this->getDefinition()));
    }
}