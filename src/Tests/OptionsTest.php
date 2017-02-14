<?php

namespace InputValidation\Tests;

use TestTools\TestCase\UnitTestCase;

/**
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class OptionsTest extends UnitTestCase
{
    /**
     * @var Options
     */
    protected $options;

    public function setUp()
    {
        $this->options = $this->get('options');
    }

    public function testGetCountries()
    {
        $result = $this->options->getCountries();

        $this->assertInternalType('array', $result);
        $this->assertCount(248, $result);
    }

    public function testGetCountriesLocaleEN()
    {
        $translator = $this->get('translator');
        $translator->setLocale('en');

        $options = new Options($translator);
        $result = $options->getCountries();

        $this->assertInternalType('array', $result);
        $this->assertCount(264, $result);

        $this->assertEquals('Germany', $result['DE']);
    }

    public function testGetCountriesLocaleRU()
    {
        $translator = $this->get('translator');
        $translator->setLocale('ru');

        $options = new Options($translator);
        $result = $options->getCountries();

        $this->assertInternalType('array', $result);
        $this->assertCount(248, $result);

        $this->assertEquals('Германия', $result['DE']);
    }

    public function testGet()
    {
        $result = $this->options->get('countries');

        $this->assertInternalType('array', $result);
        $this->assertCount(248, $result);
        $this->assertEquals('Deutschland', $result['DE']);
    }

    public function testGetOptionsPath()
    {
        $this->options->setOptionsPath('FooBar/Baz');
        $result = $this->options->getOptionsPath();
        $this->assertEquals('FooBar/Baz', $result);
    }
}