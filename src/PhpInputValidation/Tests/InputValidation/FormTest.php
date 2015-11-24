<?php

namespace PhpInputValidation\Tests;

use PhpInputValidation\Options;
use TestTools\TestCase\UnitTestCase;
use PhpInputValidation\Form;

/**
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class FormTest extends UnitTestCase
{
    /**
     * @var Form
     */
    protected $form;

    public function setUp()
    {
        $this->form = $this->get('form');
    }

    public function testSetLocale()
    {
        $this->form->setLocale('de');

        $result = $this->form->getLocale();

        $this->assertEquals('de', $result);
    }

    public function testGetAsArray()
    {
        $result = $this->form->getAsArray();
        $this->assertEquals(array(), $result);
    }

    public function testSetAllValues()
    {
        $this->setExpectedException('\PhpInputValidation\Exception\FormException');
        $values = array('foo' => 'bar', 'x' => 'y');
        $this->form->setAllValues($values);
        $result = $this->form->getValues();
        $this->assertEquals($values, $result);
    }

    public function testSetWritableValues()
    {
        $this->form->setDefinition(
            array(
                'firstname' => array(
                    'readonly' => true
                ),
                'lastname' => array(
                    'readonly' => false
                ),
                'company' => array(
                    'type' => 'string'
                ),
                'foo.bar' => array(
                    'default' => 'foo',
                    'type' => 'string'
                )
            )
        );

        $values = array('firstname' => 'foo', 'lastname' => 'bar', 'company' => 'xyz');

        $this->form->setWritableValues($values);

        $result = $this->form->getValues();

        $this->assertEquals(null, $result['firstname']);
        $this->assertEquals($values['lastname'], $result['lastname']);
        $this->assertEquals($values['company'], $result['company']);
        $this->assertEquals('foo', $result['foo.bar']);
    }

    public function testSetWritableValuesOnPageSuccess()
    {
        $this->form->setDefinition(
            array(
                'firstname' => array(
                    'readonly' => true,
                    'page' => 1
                ),
                'lastname' => array(
                    'readonly' => false,
                    'page' => 2
                ),
                'company' => array(
                    'type' => 'string',
                    'page' => 2
                ),
                'mustsee' => array(
                    'type' => 'bool',
                    'checkbox' => true,
                    'page' => 2
                ),
                'bar' => array(
                    'default' => 'foo',
                    'type' => 'string',
                    'page' => 3
                )
            )
        );

        $values = array('lastname' => 'foo', 'company' => 'bar');

        $this->form->setWritableValuesOnPage($values, 2);

        $result = $this->form->getValues();

        $this->assertEquals(null, $result['firstname']);
        $this->assertEquals($values['lastname'], $result['lastname']);
        $this->assertEquals($values['company'], $result['company']);
        $this->assertEquals('foo', $result['bar']);
        $this->assertEquals(false, $result['mustsee']);
    }

    public function testGetFirstError()
    {
        $this->form->setDefinition(
            array(
                'firstname' => array(
                    'type' => 'string',
                    'min' => 1,
                    'max' => 100,
                    'caption' => 'Vorname'
                ),
                'lastname' => array(
                    'type' => 'string',
                    'min' => 3,
                    'max' => 100,
                    'caption' => 'Nachname'
                ),
                'email' => array(
                    'type' => 'email'
                )
            )
        );

        $values = array(
            'firstname' => 'Jens',
            'lastname' => 'Mo',
            'email' => 'xyz'
        );

        $this->form->setWritableValues($values);

        $this->form->validate();

        $errors = $this->form->getErrors();

        $this->assertEquals(2, count($errors));

        $expected = 'Nachname ist zu kurz (min. 3 Zeichen)';
        $result = $this->form->getFirstError();

        $this->assertEquals($expected, $result);
    }

    public function testGetErrorsOnPage()
    {
        $this->form->setDefinition(
            array(
                'firstname' => array(
                    'readonly' => true,
                    'page' => 1
                ),
                'lastname' => array(
                    'readonly' => false,
                    'page' => 2
                ),
                'company' => array(
                    'type' => 'string',
                    'page' => 2
                ),
                'mustsee' => array(
                    'type' => 'bool',
                    'checkbox' => true,
                    'page' => 2,
                    'required' => true
                ),
                'bar' => array(
                    'type' => 'string',
                    'page' => 3,
                    'depends' => 'company'
                )
            )
        );

        $values = array('lastname' => 'foo', 'company' => 'bar', 'mustsee' => true);

        $this->form->setWritableValuesOnPage($values, 2);

        $errors = $this->form->validate()->getErrorsByPage();

        $this->assertArrayNotHasKey(1, $errors);
        $this->assertArrayNotHasKey(2, $errors);
        $this->assertArrayHasKey(3, $errors);
        $this->assertEquals(1, count($errors));
        $this->assertEquals(1, count($errors[3]['bar']));
        $this->assertFalse($this->form->isValid());
        $this->assertTrue($this->form->hasErrors());

    }

    public function testSetWritableValuesOnPageError()
    {
        $this->setExpectedException('\PhpInputValidation\Exception\FormException');

        $this->form->setDefinition(
            array(
                'firstname' => array(
                    'readonly' => true,
                    'page' => 1
                ),
                'lastname' => array(
                    'readonly' => false,
                    'page' => 2
                ),
                'company' => array(
                    'type' => 'string',
                    'page' => 2
                ),
                'bar' => array(
                    'default' => 'foo',
                    'type' => 'string',
                    'page' => 3
                )
            )
        );

        $values = array('lastname' => 'foo');

        $this->form->setWritableValuesOnPage($values, 2);
    }

    public function testValidationError()
    {
        $this->form->setDefinition(
            array(
                'firstname' => array(
                    'type' => 'string',
                    'min' => 2,
                    'max' => 10,
                    'caption' => 'Vorname'
                ),
                'temperature' => array(
                    'type' => 'numeric',
                    'min' => 29.9,
                    'max' => 50.1,
                    'caption' => 'Temperatur'
                ),
                'email' => array(
                    'type' => 'email'
                ),
                'cars' => array(
                    'caption' => 'Autos',
                    'type' => 'list',
                    'options' => array(
                        'bmw' => 'BMW',
                        'hond' => 'Honda',
                        'gmc' => 'General Motors'
                    ),
                    'min' => 1,
                    'max' => 2
                ),
                'computers' => array(
                    'caption' => 'Computer',
                    'type' => 'list',
                    'options' => array(
                        'len' => 'Lenovo',
                        'hp' => 'HP',
                        'apple' => 'Apple'
                    )
                ),
                'sports' => array(
                    'caption' => 'Sport',
                    'type' => 'list',
                    'options' => array(
                        'soccer' => 'Fussball',
                        'chess' => 'Schach',
                        'dance' => 'Tanzen'
                    )
                ),
                'bar' => array(
                    'default' => 'foo',
                    'type' => 'string',
                    'required' => true
                ),
                'spacetravel' => array(
                    'depends' => 'sports',
                    'depends_value' => 'running'
                )
            )
        );

        $values = array(
            'firstname' => 'x',
            'temperature' => 'bar',
            'email' => 'xyz',
            'cars' => array(
                'bmw' => 1,
                'hond' => 2,
                'gmc' => 3
            ),
            'computers' => array(
                'belinea' => 1
            ),
            'sports' => 'running',
            'bar' => ''
        );

        $this->form->setWritableValues($values);

        $this->form->validate();

        $errors = $this->form->getErrors();

        $this->assertEquals(8, count($errors));
    }

    public function testValidationSuccess()
    {
        $this->form->setDefinition(
            array(
                'firstname' => array(
                    'type' => 'string',
                    'min' => 2,
                    'max' => 10,
                    'caption' => 'Vorname'
                ),
                'temperature' => array(
                    'type' => 'numeric',
                    'min' => 29.9,
                    'max' => 50.1,
                    'caption' => 'Temperatur'
                ),
                'email' => array(
                    'type' => 'email'
                ),
                'cars' => array(
                    'type' => 'list',
                    'options' => array(
                        'bmw' => 'BMW',
                        'hond' => 'Honda',
                        'gmc' => 'General Motors'
                    ),
                    'min' => 1,
                    'max' => 2
                ),
                'computers' => array(
                    'type' => 'list',
                    'options' => array(
                        'len' => 'Lenovo',
                        'hp' => 'HP',
                        'apple' => 'Apple'
                    )
                ),
                'nothing' => array(
                    'type' => 'list',
                    'checkbox' => true,
                    'options' => array(
                        'len' => 'Lenovo',
                        'hp' => 'HP',
                        'apple' => 'Apple'
                    )
                ),
                'sports' => array(
                    'caption' => 'Sport',
                    'options' => array(
                        'soccer' => 'Fussball',
                        'chess' => 'Schach',
                        'dance' => 'Tanzen'
                    )
                ),
                'fun' => array(
                    'default' => 'for_me'
                ),
                'spacetravel' => array(
                    'depends' => 'sports',
                    'depends_value' => 'chess'
                ),
                'drink' => array(
                    'caption' => 'Trinken',
                    'depends' => 'sports',
                    'depends_last_option' => true
                ),
                'vehicle' => array(
                    'caption' => 'Fahrzeug',
                    'type' => 'float'
                ),
                'drive' => array(
                    'depends' => 'vehicle',
                    'depends_value_empty' => true
                ),
                'between' => array(
                    'type' => 'int',
                    'min' => 1,
                    'max' => 10
                )
            )
        );

        $values = array(
            'firstname' => 'Michael',
            'temperature' => 31,
            'email' => 'xyz@ibm.com',
            'cars' => array(
                'bmw' => 1,
                'hond' => 2
            ),
            'computers' => array(
                'apple' => 1
            ),
            'sports' => 'dance',
            'spacetravel' => 'hello',
            'drink' => true,
            'vehicle' => '1,1234',
            'between' => 2
        );

        $this->form->setWritableValues($values);

        $this->form->validate();

        $this->assertEquals('for_me', $this->form->fun);

        $errors = $this->form->getErrors();

        $this->assertEquals(0, count($errors));
        $this->assertTrue($this->form->isValid());
        $this->assertFalse($this->form->hasErrors());
    }

    public function testDefinedWritableValuesSuccess()
    {
        $this->form->setDefinition(
            array(
                'firstname' => array(
                    'type' => 'string',
                    'min' => 2,
                    'max' => 10,
                    'caption' => 'Vorname'
                ),
                'temperature' => array(
                    'type' => 'numeric',
                    'min' => 29.9,
                    'max' => 50.1,
                    'caption' => 'Temperatur'
                ),
                'email' => array(
                    'type' => 'email'
                ),
                'cars' => array(
                    'type' => 'list',
                    'options' => array(
                        'bmw' => 'BMW',
                        'hond' => 'Honda',
                        'gmc' => 'General Motors'
                    ),
                    'min' => 1,
                    'max' => 2
                ),
                'computers' => array(
                    'type' => 'list',
                    'checkbox' => true,
                    'options' => array(
                        'len' => 'Lenovo',
                        'hp' => 'HP',
                        'apple' => 'Apple'
                    )
                ),
                'more_computers' => array(
                    'type' => 'list',
                    'checkbox' => true,
                    'options' => array(
                        0 => 'Lenovo',
                        1 => 'HP',
                        2 => 'Apple'
                    ),
                    'min' => 2
                ),
                'nothing' => array(
                    'type' => 'list',
                    'checkbox' => true,
                    'options' => array(
                        'len' => 'Lenovo',
                        'hp' => 'HP',
                        'apple' => 'Apple'
                    )
                ),
                'sports' => array(
                    'caption' => 'Sport',
                    'options' => array(
                        'soccer' => 'Fussball',
                        'chess' => 'Schach',
                        'dance' => 'Tanzen'
                    )
                ),
                'spacetravel' => array(
                    'depends' => 'sports',
                    'depends_value' => 'chess'
                ),
                'drink' => array(
                    'caption' => 'Trinken',
                    'depends' => 'sports',
                    'depends_last_option' => true
                ),
                'vehicle' => array(
                    'caption' => 'Fahrzeug',
                    'type' => 'float'
                ),
                'between' => array(
                    'type' => 'int',
                    'min' => 1,
                    'max' => 10
                )
            )
        );

        $values = array(
            'firstname' => 'Michael',
            'temperature' => 31,
            'email' => 'xyz@ibm.com',
            'cars' => array(
                'bmw' => 1,
                'hond' => 2
            ),
            'computers' => array(
                'apple' => 1
            ),
            'more_computers' => array(
                '1' => 1,
                '2' => 2
            ),
            'sports' => 'dance',
            'spacetravel' => 'hello',
            'drink' => true,
            'vehicle' => '1,1234',
            'between' => 2
        );

        $this->form->setDefinedWritableValues($values);

        $this->assertEquals(array(), $this->form->nothing);

        $this->form->validate();

        $errors = $this->form->getErrors();

        $this->assertEquals(0, count($errors));

        $values = array(
            'firstname' => 'Michael',
            'temperature' => 31,
            'email' => 'xyz@ibm.com',
            'cars' => array(
                'bmw',
                'hond'
            ),
            'computers' => array(
                'apple' => 1
            ),
            'more_computers' => array(
                1, 2
            ),
            'sports' => 'dance',
            'spacetravel' => 'hello',
            'drink' => true,
            'vehicle' => '1,1234',
            'between' => 2
        );

        $this->form->setDefinedWritableValues($values);

        $this->assertEquals(array(), $this->form->nothing);

        $this->form->validate();

        $errors = $this->form->getErrors();

        $this->assertEquals(0, count($errors));
    }

    public function testChangeDefinitionException()
    {
        $this->setExpectedException('\PhpInputValidation\Exception\FormException');
        $this->form->changeDefinition('foo', array('min' => 3));
    }

    public function testChangeDefinition()
    {

        $this->form->addDefinition('foo', array('min' => 3));
        $this->form->foo = 'abc';
        $this->assertEquals(0, count($this->form->validate()->getErrors()));
        $this->form->clearErrors();

        $this->form->foo = 'ab';
        $this->assertEquals(1, count($this->form->validate()->getErrors()));
        $this->form->clearErrors();

        $this->form->changeDefinition('foo', array('min' => 5));

        $this->form->foo = 'abcd';
        $this->assertEquals(1, count($this->form->validate()->getErrors()));
        $this->form->clearErrors();

        $this->form->foo = 'abcde';
        $this->assertEquals(0, count($this->form->validate()->getErrors()));
        $this->form->clearErrors();
    }

    public function testValidateMax()
    {
        $this->form->setDefinition(
            array(
                'birthday' => array(
                    'type' => 'date',
                    'max' => 0
                ),
                'otherday' => array(
                    'type' => 'date',
                    'max' => '1981-01-22'
                ),
                'number' => array(
                    'type' => 'numeric',
                    'max' => 299
                ),
                'string' => array(
                    'type' => 'string',
                    'max' => 10
                )
            )
        );

        $values = array('birthday' => date('d.m.Y', time() + 60 * 60 * 24), 'otherday' => '22.01.1990', 'number' => 300, 'string' => 'abcdefghijk');

        $errors = $this->form->setWritableValues($values)->validate()->getErrors();

        $this->assertEquals(4, count($errors));

        $this->form->clearErrors();

        $values = array('birthday' => date('d.m.Y', time() - 60 * 60 * 24), 'otherday' => '22.01.1960', 'number' => 299, 'string' => 'abc');

        $errors = $this->form->setWritableValues($values)->validate()->getErrors();

        $this->assertEquals(0, count($errors));
    }

    public function testValidateMin()
    {
        $this->form->setDefinition(
            array(
                'birthday' => array(
                    'type' => 'date',
                    'min' => 0
                ),
                'otherday' => array(
                    'caption' => 'Other Day',
                    'type' => 'date',
                    'min' => '1981-01-22'
                ),
                'number' => array(
                    'type' => 'numeric',
                    'min' => 299
                ),
                'string' => array(
                    'type' => 'string',
                    'min' => 10
                )
            )
        );

        $values = array('birthday' => date('d.m.Y', time() - 60 * 60 * 24), 'otherday' => '22.01.1960', 'number' => 298, 'string' => 'abc');

        $errors = $this->form->setWritableValues($values)->validate()->getErrors();

        $this->assertEquals(4, count($errors));

        $this->form->clearErrors();

        $values = array('birthday' => date('d.m.Y', time() + 60 * 60 * 24), 'otherday' => '22.01.1990', 'number' => 299, 'string' => 'abcdefghijk');

        $errors = $this->form->setWritableValues($values)->validate()->getErrors();

        $this->assertEquals(0, count($errors));
    }

    public function testValidateOption()
    {
        $options = new Options($this->get('translator'));

        $this->form->setOptions($options);

        $this->form->setDefinition(
            array(
                'country' => array(
                    'caption' => 'Country',
                    'type' => 'string',
                    'options' => $this->form->getOptions('countries')
                )
            )
        );

        $values = array('country' => 'DE');

        $errors = $this->form->setWritableValues($values)->validate()->getErrors();

        $this->assertEquals(0, count($errors));

        $this->form->clearErrors();

        $values = array('country' => 'XX');

        $errors = $this->form->setWritableValues($values)->validate()->getErrors();

        $this->assertEquals(1, count($errors));
    }

    public function testDateTime()
    {
        $this->form->setDefinition(
            array(
                'start' => array(
                    'caption' => 'Date Time',
                    'type' => 'datetime'
                ),
                'end' => array(
                    'caption' => 'Date',
                    'type' => 'date'
                ),
                'lap' => array(
                    'caption' => 'Time',
                    'type' => 'time'
                ),
                'object' => array(
                    'caption' => 'Object',
                    'type' => 'date'
                )
            )
        );

        $values = array(
            'start' => '22.01.1981 12:34',
            'end' => '22.01.1990',
            'lap' => '15:12',
            'object' => new \DateTime('2000-01-01')
        );

        $errors = $this->form->setWritableValues($values)->validate()->getErrors();

        $this->assertEquals(0, count($errors));

        $form = $this->form->getAsArray();

        $this->assertInternalType('array', $form);
    }

    public function testGetValuesByTag()
    {
        $this->form->setDefinition(
            array(
                'firstname' => array(
                    'type' => 'string',
                    'min' => 2,
                    'max' => 10,
                    'caption' => 'Vorname',
                    'tags' => ['user']
                ),
                'email' => array(
                    'type' => 'email',
                    'tags' => ['user', 'example']
                ),
                'other' => array(
                    'type' => 'string',
                    'required' => true,
                    'tags' => ['other']
                )
            )
        );

        $values = array(
            'firstname' => 'Michael',
            'email' => 'xyz@ibm.com',
            'other' => 'foo'
        );

        $this->form->setDefinedWritableValues($values);

        $this->form->validate();

        $this->assertEquals('Michael', $this->form->firstname);

        $errors = $this->form->getErrors();

        $this->assertEquals(0, count($errors));
        $this->assertTrue($this->form->isValid());
        $this->assertFalse($this->form->hasErrors());

        $tagOther = $this->form->getValuesByTag('other');
        $tagOtherExpected = array('other' => 'foo');
        $this->assertEquals($tagOtherExpected, $tagOther);

        $tagUser = $this->form->getValuesByTag('user');
        $tagUserExpected = array('firstname' => 'Michael', 'email' => 'xyz@ibm.com');
        $this->assertEquals($tagUserExpected, $tagUser);

        $tagExample = $this->form->getValuesByTag('example');
        $tagExampleExpected = array('email' => 'xyz@ibm.com');
        $this->assertEquals($tagExampleExpected, $tagExample);
    }

    public function testConvertToBool()
    {
        $this->form->setDefinition(
            array(
                'wahr' => array(
                    'type' => 'bool'
                ),
                'falsch' => array(
                    'type' => 'bool'
                ),
                'default' => array(
                    'type' => 'bool',
                    'default' => null
                ),
                'undefined' => array(
                    'type' => 'bool'
                )
            )
        );

        $values = array('wahr' => 1, 'falsch' => 0);
        $this->form->setWritableValues($values);
        $this->assertTrue($this->form->wahr === true);
        $this->assertTrue($this->form->falsch === false);
        $this->assertNull($this->form->default);
        $this->assertNull($this->form->undefined);

        $values = array('wahr' => 'yes', 'falsch' => 'no');
        $this->form->setWritableValues($values);
        $this->assertTrue($this->form->wahr);
        $this->assertFalse($this->form->falsch);

        $values = array('wahr' => 'true', 'falsch' => 'false');
        $this->form->setWritableValues($values);
        $this->assertTrue($this->form->wahr);
        $this->assertFalse($this->form->falsch);

        $values = array('wahr' => true, 'falsch' => false);
        $this->form->setWritableValues($values);
        $this->assertTrue($this->form->wahr);
        $this->assertFalse($this->form->falsch);

        $values = array('wahr' => true, 'falsch' => null);
        $this->form->setWritableValues($values);
        $this->assertTrue($this->form->wahr);
        $this->assertFalse($this->form->falsch);
    }
}