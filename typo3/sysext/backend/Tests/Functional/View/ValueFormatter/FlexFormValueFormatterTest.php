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

namespace TYPO3\CMS\Backend\Tests\Functional\View\ValueFormatter;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\View\ValueFormatter\FlexFormValueFormatter;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FlexFormValueFormatterTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet('typo3/sysext/backend/Tests/Functional/Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function flexFormDataWillBeDisplayedHumanReadable(): void
    {
        $fieldTcaConfig = [
            'ds' => [
                'default' => 'FILE:EXT:backend/Tests/Functional/View/ValueFormatter/Fixtures/FlexFormValueFormatter/FlexFormDataStructure.xml',
            ],
        ];
        $GLOBALS['TCA']['tt_content']['columns']['pi_flexform']['config'] = $fieldTcaConfig;
        $expectedOutput = trim(file_get_contents(__DIR__ . '/Fixtures/FlexFormValueFormatter/ValuePreview.txt'));
        $flexFormData = file_get_contents(__DIR__ . '/Fixtures/FlexFormValueFormatter/FlexFormValue.xml');

        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('tt_content');
        $connection->insert('tt_content', ['pi_flexform' => $flexFormData]);

        $flexFormValueFormatter = $this->get(FlexFormValueFormatter::class);
        $actualOutput = $flexFormValueFormatter->format(
            'tt_content',
            'pi_flexform',
            $flexFormData,
            (int)$connection->lastInsertId(),
            $fieldTcaConfig,
        );

        self::assertSame($expectedOutput, $actualOutput);
    }

    #[Test]
    public function nullResultsInEmptyString(): void
    {
        $flexFormValueFormatter = $this->get(FlexFormValueFormatter::class);
        $actualOutput = $flexFormValueFormatter->format(
            'aTableName',
            'aFieldName',
            null,
            0,
            [],
        );

        self::assertSame('', $actualOutput);
    }

    #[Test]
    public function emptyStringResultsInEmptyString(): void
    {
        $flexFormValueFormatter = $this->get(FlexFormValueFormatter::class);
        $actualOutput = $flexFormValueFormatter->format(
            'aTableName',
            'aFieldName',
            '',
            0,
            [],
        );

        self::assertSame('', $actualOutput);
    }
}
