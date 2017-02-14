<?php

namespace InputValidation\Tests;

use InputValidation\OptionsAbstract;

class Options extends OptionsAbstract
{
    protected $optionsPath = __DIR__ .'/_options';

    public function getCountries()
    {
        return $this->getYamlList('countries');
    }
}
