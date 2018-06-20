<?php

namespace Codeages\PhalconBiz\Validator;

class ValidationException extends \InvalidArgumentException
{
    protected $messages;

    public function __construct(array $messages)
    {
        $this->messages = $messages;
        parent::__construct($this->convertMessagesToString($messages));
    }

    protected function getMessages()
    {
        return $this->messages;
    }

    protected function convertMessagesToString($messages)
    {
        $messageStr = 'Validate errors: ';

        $errors = [];
        foreach ($messages as $field => $fieldMessages) {
            $errors = array_merge($errors, array_values($fieldMessages));
        }
        $errors = implode(', ', $errors);

        return $messageStr.$errors.'.';
    }
}
