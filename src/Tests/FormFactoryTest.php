<?php

namespace InputValidation\Tests;

use InputValidation\FormFactory;
use TestTools\TestCase\UnitTestCase;

/**
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class FormFactoryTest extends UnitTestCase
{
    /**
     * @var FormFactory
     */
    protected $factory;

    public function setUp()
    {
        $translator = $this->get('translator');
        $validator = $this->get('validator');

        $this->factory = new FormFactory ($translator, $validator);
    }

    public function testGetForm()
    {
        $this->factory->setFactoryNamespace('');
        $this->factory->setFactoryPostfix('');
        $this->assertInstanceOf('InputValidation\Tests\UserForm', $this->factory->getForm('InputValidation\Tests\UserForm'));

        $this->factory->setFactoryNamespace('');
        $this->factory->setFactoryPostfix('Form');
        $this->assertInstanceOf('InputValidation\Tests\UserForm', $this->factory->getForm('InputValidation\Tests\User'));

        $this->factory->setFactoryNamespace('InputValidation\Tests');
        $this->factory->setFactoryPostfix('Form');
        $this->assertInstanceOf('InputValidation\Tests\UserForm', $this->factory->getForm('User'));

        $this->factory->setFactoryNamespace('InputValidation\Tests');
        $this->factory->setFactoryPostfix('');
        $this->assertInstanceOf('InputValidation\Tests\UserForm', $this->factory->getForm('UserForm'));
    }

    public function testGetFactoryNamespace()
    {
        $this->assertEquals('', $this->factory->getFactoryNamespace());
        $this->factory->setFactoryNamespace('InputValidation\Tests');
        $this->assertEquals('\InputValidation\Tests', $this->factory->getFactoryNamespace());
    }

    public function testGetFactoryPostfix()
    {
        $this->assertEquals('Form', $this->factory->getFactoryPostfix());
        $this->factory->setFactoryPostfix('');
        $this->assertEquals('', $this->factory->getFactoryPostfix());
    }

    /**
     * @expectedException \InputValidation\Exception\FactoryException
     */
    public function testGetFormException()
    {
        $this->factory->getForm('FooBar');
    }
}