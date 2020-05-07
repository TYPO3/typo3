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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Tests related to DataHandler slug unique handling
 */
class SlugUniqueTest extends AbstractDataHandlerActionTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontendSite(1);
        $this->importCSVDataSet(__DIR__ . '/DataSet/TestSlugUniqueBase.csv');
    }

    /**
     * Data provider for differentUniqueEvalSettingsDeDuplicateSlug
     * @return array
     */
    public function getEvalSettingDataProvider(): array
    {
        return [
            'uniqueInSite' => ['uniqueInSite'],
            'unique' => ['unique'],
            'uniqueInPid' => ['uniqueInPid'],
        ];
    }

    /**
     * @dataProvider getEvalSettingDataProvider
     * @test
     * @param string $uniqueSetting
     */
    public function differentUniqueEvalSettingsDeDuplicateSlug(string $uniqueSetting)
    {
        $GLOBALS['TCA']['pages']['columns']['slug']['config']['eval'] = $uniqueSetting;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->enableLogging = false;
        $dataHandler->start(
            [
                'pages' => [
                    3 => [
                        'title' => 'Page One',
                        'slug' => 'page-one',
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        $this->assertCSVDataSet('typo3/sysext/core/Tests/Functional/DataHandling/DataHandler/DataSet/TestSlugUniqueResult.csv');
    }
}
