<?php

namespace InputValidation\Form\Options;

use InputValidation\Form\OptionsAbstract;
use InputValidation\Exception\OptionsException as Exception;

/**
 * Form options list class for JSON data sources
 *
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class JsonOptions extends OptionsAbstract
{
    /**
     * @param string $listName
     * @throws Exception
     * @return array
     */
    protected function getJsonList(string $listName)
    {
        $locale = $this->getLocale();
        $defaultLocale = $this->getDefaultLocale();
        $filename = $this->getOptionsListPath($listName) . DIRECTORY_SEPARATOR . $locale . '.json';

        if (!file_exists($filename)) {
            $filename = $this->getOptionsListPath($listName) . DIRECTORY_SEPARATOR . $defaultLocale . '.json';
        }

        if (!file_exists($filename)) {
            throw new Exception('File not found: ' . $filename);
        }

        $result = json_decode(file_get_contents($filename), true);

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
        return $this->getJsonList($listName);
    }
}