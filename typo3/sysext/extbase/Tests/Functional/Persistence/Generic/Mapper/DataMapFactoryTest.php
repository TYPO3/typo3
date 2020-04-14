<?php

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence\Generic\Mapper;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DataMapFactoryTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory
     */
    protected $dataMapFactory;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/blog_example'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['extbase', 'fluid'];

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface The object manager
     */
    protected $objectManager;

    /**
     * Sets up this test suite.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->dataMapFactory = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::class);

        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
    }

    /**
     * @test
     */
    public function classSettingsAreResolved()
    {
        $dataMap = $this->dataMapFactory->buildDataMap(\ExtbaseTeam\BlogExample\Domain\Model\Administrator::class);

        self::assertInstanceOf(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap::class, $dataMap);
        self::assertEquals('ExtbaseTeam\BlogExample\Domain\Model\Administrator', $dataMap->getRecordType());
        self::assertEquals('fe_users', $dataMap->getTableName());
    }

    /**
     * @test
     */
    public function columnMapPropertiesAreResolved()
    {
        $dataMap = $this->dataMapFactory->buildDataMap(\ExtbaseTeam\BlogExample\Domain\Model\TtContent::class);

        self::assertInstanceOf(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap::class, $dataMap);
        self::assertNull($dataMap->getColumnMap('thisPropertyDoesNotExist'));

        $headerColumnMap = $dataMap->getColumnMap('header');

        self::assertInstanceOf(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\ColumnMap::class, $headerColumnMap);
        self::assertEquals('header', $headerColumnMap->getPropertyName());
        self::assertEquals('header', $headerColumnMap->getColumnName());
    }
}
