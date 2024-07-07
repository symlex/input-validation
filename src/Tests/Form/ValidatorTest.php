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

    public function setUp(): void
    {
        $this->validator = $this->get('form.validator');
    }

    public function testGetFormException () {
        $this->expectException('\InputValidation\Exception\ValidatorException');
        $this->validator->getForm();
    }
}