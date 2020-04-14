<?php

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

namespace TYPO3\CMS\Frontend\Tests\Functional\Controller;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class TypoScriptFrontendControllerTest extends FunctionalTestCase
{
    /**
     * @var TypoScriptFrontendController
     */
    protected $tsFrontendController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/fixtures.xml');

        $this->tsFrontendController = $this->getAccessibleMock(
            TypoScriptFrontendController::class,
            ['dummy'],
            [],
            '',
            false
        );

        $this->tsFrontendController->_set('sys_page', new PageRepository());
    }

    /**
     * @test
     */
    public function getFirstTimeValueForRecordReturnCorrectData()
    {
        self::assertSame(
            $this->getFirstTimeValueForRecordCall('tt_content:2', 1),
            2,
            'The next start/endtime should be 2'
        );
        self::assertSame(
            $this->getFirstTimeValueForRecordCall('tt_content:2', 2),
            3,
            'The next start/endtime should be 3'
        );
        self::assertSame(
            $this->getFirstTimeValueForRecordCall('tt_content:2', 4),
            5,
            'The next start/endtime should be 5'
        );
        self::assertSame(
            $this->getFirstTimeValueForRecordCall('tt_content:2', 5),
            PHP_INT_MAX,
            'The next start/endtime should be PHP_INT_MAX as there are no more'
        );
        self::assertSame(
            $this->getFirstTimeValueForRecordCall('tt_content:3', 1),
            PHP_INT_MAX,
            'Should be PHP_INT_MAX as table has not this PID'
        );
        self::assertSame(
            $this->getFirstTimeValueForRecordCall('fe_groups:2', 1),
            PHP_INT_MAX,
            'Should be PHP_INT_MAX as table fe_groups has no start/endtime in TCA'
        );
    }

    /**
     * @param string $tablePid
     * @param int $now
     * @return int
     */
    public function getFirstTimeValueForRecordCall($tablePid, $now)
    {
        return $this->tsFrontendController->_call('getFirstTimeValueForRecord', $tablePid, $now);
    }
}
