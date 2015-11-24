<?php

namespace InputValidation;

use Symfony\Component\Yaml\Parser as YamlParser;
use Symfony\Component\Translation\TranslatorInterface as Translator;
use InputValidation\Exception\OptionsException as Exception;

/**
 * Default options list for InputValidation Forms
 *
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class Options implements OptionsInterface
{
    protected $translator;
    protected $yamlParser;

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
        if(!$this->translator) {
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
    protected function getParser()
    {
        if(!$this->translator) {
            throw new Exception ('Parser not set');
        }

        return $this->yamlParser;
    }

    /**
     * Shortcut to access getters
     *
     * @param string $listName
     * @return array
     */
    public function get($listName)
    {
        $method = 'get' . ucfirst($listName);

        return $this->$method();
    }

    /**
     * Returns list of countries (depending on the locale of the translator)
     *
     * @return array
     */
    public function getCountries()
    {
        $locale = $this->getLocale();

        $filename = __DIR__ . '/Options/Countries/' . $locale . '.yml';

        if(!file_exists($filename)) {
            $filename = __DIR__ . '/Options/Countries/en.yml';
        }

        $result = $this->getParser()->parse(file_get_contents($filename));

        return $result;
    }
}