<?php

namespace InputValidation;

use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\Translation\TranslatorInterface as Translator;
use InputValidation\Exception\OptionsException as Exception;

/**
 * Form options list class for YAML and JSON data sources
 *
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
abstract class OptionsAbstract implements OptionsInterface
{
    /**
     * @var Translator
     */
    protected $translator;

    /**
     * @var YamlParser
     */
    protected $yamlParser;

    /**
     * @var string
     */
    protected $optionsPath;

    /**
     * @var string
     */
    protected $defaultLocale = 'en';

    /**
     * Options constructor.
     * @param Translator $translator
     */
    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
        $this->yamlParser = new YamlParser();
    }

    /**
     * @return Translator
     * @throws Exception
     */
    protected function getTranslator()
    {
        if (!$this->translator) {
            throw new Exception ('Translator not set');
        }

        return $this->translator;
    }

    /**
     * Returns the current locale string (from translator)
     *
     * @return string
     */
    protected function getLocale()
    {
        return $this->getTranslator()->getLocale();
    }

    /**
     * Returns the YAML parser instance
     *
     * @return YamlParser
     * @throws Exception
     */
    protected function getYamlParser()
    {
        if (!$this->translator) {
            throw new Exception ('Parser not set');
        }

        return $this->yamlParser;
    }

    /**
     * Get options list (yaml or json file) directory path
     *
     * @param string $name
     * @return string
     */
    protected function getOptionsListPath(string $name = '')
    {
        $result = $this->optionsPath . DIRECTORY_SEPARATOR . $name;

        return $result;
    }

    /**
     * @param string $name
     * @return array
     */
    protected function getYamlList(string $name)
    {
        $locale = $this->getLocale();
        $defaultLocale = $this->getDefaultLocale();
        $filename = $this->getOptionsListPath($name) . DIRECTORY_SEPARATOR . $locale . '.yml';

        if (!file_exists($filename)) {
            $filename = $this->getOptionsListPath($name) . DIRECTORY_SEPARATOR . $defaultLocale . '.yml';
        }

        $result = $this->getYamlParser()->parse(file_get_contents($filename));

        return $result;
    }

    /**
     * @param string $name
     * @return array
     */
    protected function getJsonList(string $name)
    {
        $locale = $this->getLocale();
        $defaultLocale = $this->getDefaultLocale();
        $filename = $this->getOptionsListPath($name) . DIRECTORY_SEPARATOR . $locale . '.json';

        if (!file_exists($filename)) {
            $filename = $this->getOptionsListPath($name) . DIRECTORY_SEPARATOR . $defaultLocale . '.json';
        }

        $result = json_decode(file_get_contents($filename), true);

        return $result;
    }

    /**
     * Sets the default value
     *
     * @param string $locale
     * @return string
     */
    public function setDefaultLocale(string $locale)
    {
        $this->defaultLocale = $locale;
    }

    /**
     * Returns the default value
     *
     * @return string
     */
    public function getDefaultLocale()
    {
        return $this->defaultLocale;
    }

    /**
     * @param string $path
     */
    public function setOptionsPath(string $path)
    {
        $this->optionsPath = $path;
    }

    /**
     * Get full options directory path
     *
     * @throws Exception
     * @return string
     */
    public function getOptionsPath()
    {
        $result = $this->optionsPath;

        if(empty($result)) {
            throw new Exception('Please use setOptionsPath() to set a path to the options lists');
        }

        return $result;
    }

    /**
     * Shortcut to access getters
     *
     * @param string $listName
     * @return array
     */
    public function get(string $listName)
    {
        $method = 'get' . ucfirst($listName);

        return $this->$method();
    }
}