<?php

namespace InputValidation\Tests\Form;

use TestTools\TestCase\UnitTestCase;
use InputValidation\Form\Validator;

/**
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 */
class ValidatorTest extends UnitTestCase {
    /**
     * @var Validator
     */
    protected $validator;

    public function setUp () {
        $this->validator = $this->get('form.validator');
    }

    /**
     * @expectedException \InputValidation\Exception\ValidatorException
     */
    public function testGetFormException () {
        $this->validator->getForm();
    }
}