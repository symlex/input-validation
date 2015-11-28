<?php

namespace InputValidation;

use Symfony\Component\Translation\TranslatorInterface as Translator;
use InputValidation\Exception\FactoryException;

/**
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class FormFactory
{
    /**
     * Namespace used by the factory method (see getForm())
     *
     * @var string
     */
    protected $_factoryNamespace = '';

    /**
     * Class name postfix used by the factory method (see getForm())
     *
     * @var string
     */
    protected $_factoryPostfix = 'Form';

    /**
     * @var Translator
     */
    protected $_translator = null;

    /**
     * @var Validator
     */
    protected $_validator = null;

    /**
     * Form constructor.
     *
     * @param Translator $translator
     * @param Validator $validator
     */
    public function __construct(Translator $translator, Validator $validator)
    {
        $this->setTranslator($translator);
        $this->setValidator($validator);
    }

    /**
     * Creates a new form instance
     *
     * @param string $name Form class name
     * @param array $params Optional config parameters for init()
     * @throws FactoryException
     * @return Form
     */
    public function getForm($name, array $params = array())
    {
        if (empty($name)) {
            throw new FactoryException ('getForm() requires a form name as first argument');
        }

        $className = $this->getFactoryNamespace() . '\\' . $name . $this->getFactoryPostfix();

        if (!class_exists($className)) {
            throw new FactoryException ('Form class "' . $className . '" does not exist');
        }

        $result = $this->createFormInstance($className, $params);

        return $result;
    }

    /**
     * Returns a new form instance of $className
     *
     * @param string $className
     * @param array $params
     * @return Form
     * @throws FactoryException
     */
    protected function createFormInstance($className, array $params)
    {
        $result = new $className ($this->getTranslator(), $this->getValidator(), $params);

        return $result;
    }

    /**
     * Sets namespace
     *
     * @param string $namespace
     */
    public function setFactoryNamespace($namespace)
    {
        $this->_factoryNamespace = (string)$namespace;
    }

    /**
     * Returns absolute namespace
     *
     * @return string
     */
    public function getFactoryNamespace()
    {
        $result = $this->_factoryNamespace;

        if ($result && strpos($result, '\\') !== 0) {
            $result = '\\' . $result;
        }

        return $result;
    }

    /**
     * Sets class name postfix
     *
     * @param string $postfix
     */
    public function setFactoryPostfix($postfix)
    {
        $this->_factoryPostfix = (string)$postfix;
    }

    /**
     * Returns class name postfix
     *
     * @return string
     */
    public function getFactoryPostfix()
    {
        return $this->_factoryPostfix;
    }

    /**
     * @return Translator
     * @throws FactoryException
     */
    public function getTranslator()
    {
        if (!$this->_translator) {
            throw new FactoryException('Translator was not set');
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
     * @throws FactoryException
     */
    public function getValidator()
    {
        if (!$this->_validator) {
            throw new FactoryException('Validator was not set');
        }

        return $this->_validator;
    }

    /**
     * @param Validator $validator
     * @return $this
     */
    public function setValidator(Validator $validator)
    {
        $this->_validator = $validator;

        return $this;
    }
}