<?php

namespace InputValidation\Tests;

use \InputValidation\Form;

class UserForm extends Form
{
    protected function init(array $params = array())
    {
        $definition = [
            'username' => [
                'type' => 'string',
                'caption' => 'Username',
                'required' => true,
                'min' => 3,
                'max' => 15
            ],
            'email' => [
                'type' => 'email',
                'caption' => 'E - Mail',
                'required' => true
            ],
            'gender' => [
                'type' => 'string',
                'caption' => 'Gender',
                'required' => false,
                'options' => ['male', 'female'],
                'optional' => true
            ],
            'birthday' => [
                'type' => 'date',
                'caption' => 'Birthday',
                'required' => false
            ],
            'password' => [
                'type' => 'string',
                'caption' => 'Password',
                'required' => true,
                'min' => 5,
                'max' => 30
            ],
            'password_again' => [
                'type' => 'string',
                'caption' => 'Password confirmation',
                'required' => true,
                'matches' => 'password'
            ],
            'continent' => [
                'type' => 'string',
                'caption' => 'Region',
                'required' => true,
                'options' => ['north_america', 'south_america', 'europe', 'asia', 'australia']
            ]
        ];

        $this->setDefinition($definition);
    }
}