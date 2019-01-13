<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\tests\data\validators;

use yii\validators\Validator;

class TestValidator extends Validator
{
    private $_validatedAttributes = [];
    private $_setErrorOnValidateAttribute = false;

    public function validateAttribute($object, string $attribute): void
    {
        $this->markAttributeValidated($attribute);
        if ($this->_setErrorOnValidateAttribute == true) {
            $this->addError($object, $attribute, sprintf('%s##%s', $attribute, \get_class($object)));
        }
    }

    protected function markAttributeValidated(string $attribute, int $increaseBy = 1): void
    {
        if (!isset($this->_validatedAttributes[$attribute])) {
            $this->_validatedAttributes[$attribute] = 1;
        } else {
            $this->_validatedAttributes[$attribute] = $this->_validatedAttributes[$attribute] + $increaseBy;
        }
    }

    public function countAttributeValidations(string $attritubte): int
    {
        return $this->_validatedAttributes[$attritubte] ?? 0;
    }

    public function isAttributeValidated(string $attribute): bool
    {
        return isset($this->_validatedAttributes[$attribute]);
    }

    public function enableErrorOnValidateAttribute()
    {
        $this->_setErrorOnValidateAttribute = true;
    }
}
