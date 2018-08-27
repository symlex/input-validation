<?php

namespace InputValidation\Tests\Form\Options;

use InputValidation\Form\Options\JsonOptions;
use Symfony\Component\Translation\Translator;
use TestTools\TestCase\UnitTestCase;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 */
class JsonOptionsTest extends UnitTestCase
{
    /**
     * @var JsonOptions
     */
    protected $options;

    /**
     * @var Translator
     */
    protected $translator;

    public function setUp()
    {
        $container = $this->getContainer();
        $this->translator = $container->get('translator');
        $this->options = $container->get('form.json_options');
    }

    public function testGetCountries()
    {
        $result = $this->options->get('countries');

        $this->assertInternalType('array', $result);
        $this->assertCount(253, $result);
    }

    public function testGetCountriesLocaleEN()
    {
        $this->translator->setLocale('en');

        $result = $this->options->get('countries');

        $this->assertInternalType('array', $result);
        $this->assertCount(253, $result);

        $this->assertEquals('Germany', $result['DE']);
    }

    public function testGetCountriesLocaleRU()
    {
        $this->translator->setLocale('ru');

        $result = $this->options->get('countries');

        $this->assertInternalType('array', $result);
        $this->assertCount(253, $result);

        $this->assertEquals('Germany', $result['DE']);
    }

    public function testGetCountriesLocaleDE()
    {
        $this->translator->setLocale('de');

        $result = $this->options->get('countries');

        $this->assertInternalType('array', $result);
        $this->assertCount(253, $result);

        $this->assertEquals('Deutschland', $result['DE']);
    }

    public function testGet()
    {
        $result = $this->options->get('countries');

        $this->assertInternalType('array', $result);
        $this->assertCount(253, $result);
        $this->assertEquals('Deutschland', $result['DE']);
    }

    public function testGetOptionsPath()
    {
        $this->options->setOptionsPath('FooBar/Baz');
        $result = $this->options->getOptionsPath();
        $this->assertEquals('FooBar/Baz', $result);
    }
}