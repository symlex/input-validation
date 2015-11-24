<?php

namespace PhpInputValidation\Tests;

use TestTools\TestCase\UnitTestCase;
use PhpInputValidation\Validator;

/**
 * @author Michael Mayer <michael@lastzero.net>
 * @license MIT
 */
class ValidatorTest extends UnitTestCase {
    /**
     * @var Validator
     */
    protected $validator;

    public function setUp () {
        $this->validator = $this->get('validator');
    }

    /**
     * @expectedException \PhpInputValidation\Exception\ValidatorException
     */
    public function testGetFormException () {
        $this->validator->getForm();
    }
}