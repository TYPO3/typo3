<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Extbase\Tests\Functional\Persistence;

use ExtbaseTeam\A\Domain\Repository\ARepository;
use ExtbaseTeam\B\Domain\Repository\BRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ImplementationClassNameTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/class_overriding/a',
        'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/class_overriding/b',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(GeneralUtility::getFileAbsFileName(
            'typo3/sysext/extbase/Tests/Functional/Fixtures/Extensions/class_overriding/b/tx_a_domain_model_a.csv'
        ));
    }

    /**
    * @test
    */
    public function testARepositoryObjectsAreTakenFromSession(): void
    {
        $aRepository = $this->getContainer()->get(ARepository::class);
        $a1 = $aRepository->findByUid(1);
        $a2 = $aRepository->findByUid(1);

        self::assertSame($a1, $a2);
    }

    /**
    * @test
    */
    public function testBRepositoryObjectsAreTakenFromSession(): void
    {
        $bRepository = $this->getContainer()->get(BRepository::class);
        $b1 = $bRepository->findByUid(1);
        $b2 = $bRepository->findByUid(1);

        self::assertSame($b1, $b2);
    }
}
