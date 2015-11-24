<?php

namespace InputValidation\Tests;

use TestTools\TestCase\UnitTestCase;
use InputValidation\Validator;

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
     * @expectedException \InputValidation\Exception\ValidatorException
     */
    public function testGetFormException () {
        $this->validator->getForm();
    }
}