<?php

namespace InputValidation\Tests\Form;

use InputValidation\Form\Factory;
use TestTools\TestCase\UnitTestCase;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 */
class FactoryTest extends UnitTestCase
{
    /**
     * @var Factory
     */
    protected $factory;

    public function setUp()
    {
        $this->factory = $this->get('form.factory');
    }

    public function testGetForm()
    {
        $this->factory->setFactoryNamespace('');
        $this->factory->setFactoryPostfix('');
        $this->assertInstanceOf('InputValidation\Tests\UserForm', $this->factory->create('InputValidation\Tests\UserForm'));

        $this->factory->setFactoryNamespace('');
        $this->factory->setFactoryPostfix('Form');
        $this->assertInstanceOf('InputValidation\Tests\UserForm', $this->factory->create('InputValidation\Tests\User'));

        $this->factory->setFactoryNamespace('InputValidation\Tests');
        $this->factory->setFactoryPostfix('Form');
        $this->assertInstanceOf('InputValidation\Tests\UserForm', $this->factory->create('User'));

        $this->factory->setFactoryNamespace('InputValidation\Tests');
        $this->factory->setFactoryPostfix('');
        $this->assertInstanceOf('InputValidation\Tests\UserForm', $this->factory->create('UserForm'));
    }

    public function testGetFactoryNamespace()
    {
        $this->assertEquals('\InputValidation', $this->factory->getFactoryNamespace());
        $this->factory->setFactoryNamespace('InputValidation\Tests');
        $this->assertEquals('\InputValidation\Tests', $this->factory->getFactoryNamespace());
    }

    public function testGetFactoryPostfix()
    {
        $this->assertEquals('', $this->factory->getFactoryPostfix());
        $this->factory->setFactoryPostfix('Form');
        $this->assertEquals('Form', $this->factory->getFactoryPostfix());
    }

    /**
     * @expectedException \InputValidation\Exception\FactoryException
     */
    public function testGetFormException()
    {
        $this->factory->create('FooBar');
    }
}