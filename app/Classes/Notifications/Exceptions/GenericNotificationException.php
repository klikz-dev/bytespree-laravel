<?php

namespace App\Classes\Notifications\Exceptions;

use Exception;

class GenericNotificationException extends Exception
{
    protected $additional_details = NULL;

    public function __construct(string $message = "", $additional_details = NULL)
    {
        parent::__construct($message);
        $this->additional_details = $additional_details;
    }
}
