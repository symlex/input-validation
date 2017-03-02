<?php

namespace InputValidation\Form;

use Symfony\Component\Translation\TranslatorInterface as Translator;
use InputValidation\Exception\FactoryException;
use InputValidation\Form;

/**
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class Factory
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
     * @var OptionsInterface
     */
    private $options;

    /**
     * Form constructor.
     *
     * @param Translator $translator
     * @param Validator $validator
     * @param OptionsInterface $options
     */
    public function __construct(Translator $translator, Validator $validator, OptionsInterface $options)
    {
        $this->setTranslator($translator);
        $this->setValidator($validator);
        $this->setOptions($options);
    }

    /**
     * Returns a new form instance of the given name
     *
     * @param string $name Type of form
     * @param array $params Optional config parameters for init()
     * @throws FactoryException
     * @return Form
     */
    public function create(string $name, array $params = array()): Form
    {
        if (empty($name)) {
            throw new FactoryException ('create() requires a non-empty form name as first argument');
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
    protected function createFormInstance(string $className, array $params)
    {
        $result = new $className ($this->getTranslator(), $this->getValidator(), $this->getOptions(), $params);

        return $result;
    }

    /**
     * Sets namespace
     *
     * @param string $namespace
     */
    public function setFactoryNamespace(string $namespace)
    {
        $this->_factoryNamespace = $namespace;
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
    public function setFactoryPostfix(string $postfix)
    {
        $this->_factoryPostfix = $postfix;
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
    protected function getTranslator()
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
    protected function setTranslator(Translator $translator)
    {
        $this->_translator = $translator;

        return $this;
    }

    /**
     * @return Validator
     * @throws FactoryException
     */
    protected function getValidator()
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
    protected function setValidator(Validator $validator)
    {
        $this->_validator = $validator;

        return $this;
    }

    /**
     * @param OptionsInterface $options
     * @return $this
     */
    protected function setOptions(OptionsInterface $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return OptionsInterface
     */
    protected function getOptions()
    {
        return $this->options;
    }
}