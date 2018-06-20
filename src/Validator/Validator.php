<?php

namespace Codeages\PhalconBiz\Validator;

use Particle\Validator\Validator as BaseValidator;

class Validator extends BaseValidator
{
    public function filter(array $values, $context = self::DEFAULT_CONTEXT)
    {
        $result = $this->validate($values);
        if ($result->isNotValid()) {
            throw new ValidationException($result->getMessages());
        }

        return $result->getValues();
    }
}
