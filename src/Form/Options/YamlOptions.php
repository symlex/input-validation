<?php

namespace InputValidation\Form\Options;

use InputValidation\Form\OptionsAbstract;
use Symfony\Component\Yaml\Parser as YamlParser;
use InputValidation\Exception\OptionsException as Exception;

/**
 * Form options list class for YAML and JSON data sources
 *
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 */
class YamlOptions extends OptionsAbstract
{
    /**
     * @var YamlParser
     */
    protected $yamlParser;

    /**
     * Returns the YAML parser instance
     *
     * @return YamlParser
     * @throws Exception
     */
    protected function getYamlParser()
    {
        if (!$this->yamlParser) {
            $this->yamlParser = new YamlParser();
        }

        return $this->yamlParser;
    }

    /**
     * @param string $name
     * @throws Exception
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

        if (!file_exists($filename)) {
            throw new Exception('File not found: ' . $filename);
        }

        $result = $this->getYamlParser()->parse(file_get_contents($filename));

        return $result;
    }

    /**
     * Returns the options list
     *
     * @param string $listName
     * @return array
     */
    public function get(string $listName)
    {
        return $this->getYamlList($listName);
    }
}