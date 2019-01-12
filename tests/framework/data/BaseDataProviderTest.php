<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\tests\framework\data;

use yii\data\BaseDataProvider;
use yii\tests\TestCase;

/**
 * @group data
 */
class BaseDataProviderTest extends TestCase
{
    public function testGenerateId()
    {
        $rc = new \ReflectionClass(BaseDataProvider::class);
        $rp = $rc->getProperty('counter');
        $rp->setAccessible(true);
        $rp->setValue(null);

        $this->assertNull((new ConcreteDataProvider())->id);
        $this->assertNotNull((new ConcreteDataProvider())->id);
    }
}

/**
 * ConcreteDataProvider.
 */
class ConcreteDataProvider extends BaseDataProvider
{
    /**
     * {@inheritdoc}
     */
    protected function prepareModels(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareKeys(array $models): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareTotalCount(): int
    {
        return 0;
    }
}
