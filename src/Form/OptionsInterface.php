<?php

namespace InputValidation\Form;

/**
 * Form options list interface
 *
 * @author Michael Mayer <michael@liquidbytes.net>
 * @license MIT
 */
interface OptionsInterface
{
    public function get(string $listName);
}