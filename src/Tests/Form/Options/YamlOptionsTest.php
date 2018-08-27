<?php

namespace InputValidation\Tests\Form\Options;

use InputValidation\Form\Options\YamlOptions;
use Symfony\Component\Translation\Translator;
use TestTools\TestCase\UnitTestCase;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 */
class YamlOptionsTest extends UnitTestCase
{
    /**
     * @var YamlOptions
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
        $this->options = $container->get('form.yaml_options');
    }

    public function testGetCountries()
    {
        $result = $this->options->get('countries');

        $this->assertInternalType('array', $result);
        $this->assertCount(248, $result);
    }

    public function testGetCountriesLocaleEN()
    {
        $this->translator->setLocale('en');

        $result = $this->options->get('countries');

        $this->assertInternalType('array', $result);
        $this->assertCount(264, $result);

        $this->assertEquals('Germany', $result['DE']);
    }

    public function testGetCountriesLocaleRU()
    {
        $this->translator->setLocale('ru');

        $result = $this->options->get('countries');

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