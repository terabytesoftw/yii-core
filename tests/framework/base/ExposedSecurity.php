<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\tests\framework\base;

use yii\base\Security;

/**
 * ExposedSecurity exposes protected methods for direct testing.
 */
class ExposedSecurity extends Security
{
    /**
     * {@inheritdoc}
     */
    public function hkdf(string $algo, string $inputKey, string $salt = null, string $info = null, int $length = 0): string
    {
        return parent::hkdf($algo, $inputKey, $salt, $info, $length);
    }

    /**
     * {@inheritdoc}
     */
    public function pbkdf2(string $algo, string $password, string $salt, int $iterations, int $length = 0): string
    {
        return parent::pbkdf2($algo, $password, $salt, $iterations, $length);
    }
}
