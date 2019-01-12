<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\serialize;

use yii\base\BaseObject;

/**
 * PhpSerializer uses native PHP `serialize()` and `unserialize()` functions for the serialization.
 *
 * @author Paul Klimov <klimov.paul@gmail.com>
 * @since 3.0.0
 */
class PhpSerializer extends BaseObject implements SerializerInterface
{
    /**
     * {@inheritdoc}
     */
    public function serialize($value): string
    {
        return serialize($value);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize(string $value)
    {
        return unserialize($value);
    }
}