<?php

namespace Servebolt\Optimizer\Exceptions\ErrorTranslator;

/**
 * Class ErrorTranslator
 * @package Servebolt\Optimizer\Exceptions\ErrorTranslator
 */
abstract class ErrorTranslator
{

    /**
     * @var array
     */
    protected $errors;

    /**
     * ErrorTranslator constructor.
     * @param $errors
     */
    public function __construct($errors)
    {
        $this->errors = $errors;
    }

    /**
     * Attempt to translate error messages to something more user friendly.
     *
     * @return array
     */
    public function translate(): array
    {
        return array_map(function($error) {
            if (array_key_exists($error->code, $this->definitions)) {
                $error->original_message = $error->message;
                $error->message = $this->definitions[$error->code];
            }
            return $error;
        }, $this->errors);
    }
}
