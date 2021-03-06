<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Config;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink;
use Oro\Bundle\EntityConfigBundle\Entity\AbstractConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\Events;
use Oro\Bundle\EntityConfigBundle\Event\PersistConfigEvent;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderBag;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;

class FlushConfigManagerTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'Oro\Bundle\EntityConfigBundle\Tests\Unit\Fixture\DemoEntity';

    /** @var ConfigManager */
    protected $configManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $metadataFactory;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $entityConfigProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $testConfigProvider;

    /** @var ConfigProviderBag */
    protected $configProviderBag;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $modelManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $auditManager;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $configCache;

    protected function setUp()
    {
        $this->entityConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->entityConfigProvider->expects($this->any())
            ->method('getScope')
            ->will($this->returnValue('entity'));

        $this->testConfigProvider = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->testConfigProvider->expects($this->any())
            ->method('getScope')
            ->will($this->returnValue('test'));

        $this->configProviderBag = new ConfigProviderBag();
        $this->configProviderBag->addProvider($this->entityConfigProvider);
        $this->configProviderBag->addProvider($this->testConfigProvider);
        $this->container = $this->getMock('\Symfony\Component\DependencyInjection\ContainerInterface');
        $this->container->expects($this->any())
            ->method('has')
            ->will(
                $this->returnCallback(
                    function ($id) {
                        switch ($id) {
                            case 'ConfigProviderBag':
                                return true;
                            default:
                                return false;
                        }
                    }
                )
            );
        $configProviderBag = $this->configProviderBag;
        $this->container->expects($this->any())
            ->method('get')
            ->will(
                $this->returnCallback(
                    function ($id) use (&$configProviderBag) {
                        switch ($id) {
                            case 'ConfigProviderBag':
                                return $configProviderBag;
                            default:
                                return null;
                        }
                    }
                )
            );

        $this->metadataFactory = $this->getMockBuilder('Metadata\MetadataFactory')
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();
        $this->modelManager    = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigModelManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->auditManager    = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Audit\AuditManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configCache     = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigCache')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = new ConfigManager(
            $this->metadataFactory,
            $this->eventDispatcher,
            new ServiceLink($this->container, 'ConfigProviderBag'),
            $this->modelManager,
            $this->auditManager,
            $this->configCache
        );
    }

    public function testFlush()
    {
        $model = new EntityConfigModel(self::ENTITY_CLASS);

        $entityConfigId = new EntityConfigId('entity', self::ENTITY_CLASS);
        $entityConfig   = new Config($entityConfigId);
        $entityConfig->set('icon', 'test_icon');
        $entityConfig->set('label', 'test_label');
        $entityPropertyConfig = new PropertyConfigContainer(
            [
                'entity' => [
                    'items' => [
                        'icon'  => [],
                        'label' => ['options' => ['indexed' => true]]
                    ]
                ]
            ]
        );
        $this->entityConfigProvider->expects($this->once())
            ->method('getPropertyConfig')
            ->will($this->returnValue($entityPropertyConfig));

        $testConfigId = new EntityConfigId('test', self::ENTITY_CLASS);
        $testConfig   = new Config($testConfigId);
        $testConfig->set('attr1', 'test_attr1');
        $testPropertyConfig = new PropertyConfigContainer(
            [
                'entity' => [
                    'items' => [
                        'attr1' => []
                    ]
                ]
            ]
        );
        $this->testConfigProvider->expects($this->once())
            ->method('getPropertyConfig')
            ->will($this->returnValue($testPropertyConfig));

        $this->modelManager->expects($this->once())
            ->method('getEntityModel')
            ->with($entityConfigId->getClassName())
            ->will($this->returnValue($model));

        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->modelManager->expects($this->any())
            ->method('getEntityManager')
            ->will($this->returnValue($em));

        $this->setFlushExpectations($em, [$model]);

        $this->eventDispatcher->expects($this->at(0))
            ->method('dispatch')
            ->with(Events::PRE_PERSIST_CONFIG, new PersistConfigEvent($entityConfig, $this->configManager));
        $this->eventDispatcher->expects($this->at(1))
            ->method('dispatch')
            ->with(Events::PRE_PERSIST_CONFIG, new PersistConfigEvent($testConfig, $this->configManager));

        $this->configManager->persist($entityConfig);
        $this->configManager->persist($testConfig);
        $this->configManager->flush();

        $this->assertEquals(
            [
                'icon' => 'test_icon',
                'label' => 'test_label',
            ],
            $model->toArray('entity')
        );
        $this->assertEquals(
            [
                'attr1' => 'test_attr1'
            ],
            $model->toArray('test')
        );

        $this->assertCount(3, $model->getIndexedValues());
        $this->assertEquals('entity_config', $model->getIndexedValues()[0]->getScope());
        $this->assertEquals('module_name', $model->getIndexedValues()[0]->getCode());
        $this->assertEquals('entity_config', $model->getIndexedValues()[1]->getScope());
        $this->assertEquals('entity_name', $model->getIndexedValues()[1]->getCode());
        $this->assertEquals('entity', $model->getIndexedValues()[2]->getScope());
        $this->assertEquals('label', $model->getIndexedValues()[2]->getCode());
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject $em
     * @param AbstractConfigModel[]                    $models
     */
    protected function setFlushExpectations($em, $models)
    {
        $this->configCache->expects($this->once())
            ->method('deleteAllConfigurable');
        $this->auditManager->expects($this->once())
            ->method('buildLogEntry')
            ->with($this->identicalTo($this->configManager))
            ->willReturn(null);

        $em->expects($this->exactly(count($models)))
            ->method('persist')
            ->will(
                $this->returnCallback(
                    function ($obj) use (&$models) {
                        foreach ($models as $model) {
                            if ($model == $obj) {
                                return;
                            }
                        }
                        $this->fail(
                            sprintf(
                                'Expected that $em->persist(%s[%s]) is called.',
                                get_class($obj),
                                $obj instanceof FieldConfigModel ? $obj->getFieldName() : $obj->getClassName()
                            )
                        );
                    }
                )
            );
        $em->expects($this->once())
            ->method('flush');
    }
}
