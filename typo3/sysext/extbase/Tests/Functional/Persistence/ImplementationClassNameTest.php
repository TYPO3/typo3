<?php

declare(strict_types=1);
namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Class TYPO3\CMS\Extbase\Tests\Functional\ImplementationClassNameTest
 */
class ImplementationClassNameTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/class_overriding/a',
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/class_overriding/b',
    ];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['extbase', 'fluid'];

    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function setUp(): void
    {
        parent::setUp();

        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);

        $this->importCSVDataSet(GeneralUtility::getFileAbsFileName(
            'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/class_overriding/b/tx_a_domain_model_a.csv'
        ));
    }

    /**
    * @test
    */
    public function testARepositoryObjectsAreTakenFromSession(): void
    {
        $aRepository = $this->objectManager->get(\ExtbaseTeam\A\Domain\Model\ARepository::class);
        $a1 = $aRepository->findByUid(1);
        $a2 = $aRepository->findByUid(1);

        self::assertSame($a1, $a2);
    }

    /**
    * @test
    */
    public function testBRepositoryObjectsAreTakenFromSession(): void
    {
        $bRepository = $this->objectManager->get(\ExtbaseTeam\B\Domain\Model\BRepository::class);
        $b1 = $bRepository->findByUid(1);
        $b2 = $bRepository->findByUid(1);

        self::assertSame($b1, $b2);
    }
}
