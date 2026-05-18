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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests for the "eval" keyword "lower" of TCA type=email.
 */
final class EmailEvalTest extends FunctionalTestCase
{
    protected const int PAGE_ID = 200;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/EmailEval.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function emailIsLowercasedWithEvalLower(): void
    {
        $record = $this->createContentRecord(['tx_testdatahandler_email_lower' => 'Editor@Example.ORG']);

        self::assertSame('editor@example.org', $record['tx_testdatahandler_email_lower']);
    }

    #[Test]
    public function emailIsNotLowercasedWithoutEvalLower(): void
    {
        $record = $this->createContentRecord(['tx_testdatahandler_email' => 'Editor@Example.ORG']);

        self::assertSame('Editor@Example.ORG', $record['tx_testdatahandler_email']);
    }

    #[Test]
    public function emailIsLowercasedOnUpdate(): void
    {
        $contentId = $this->createContentId(['tx_testdatahandler_email_lower' => 'editor@example.org']);

        new ActionService()->modifyRecord('tt_content', $contentId, [
            'tx_testdatahandler_email_lower' => 'Another.Editor@Example.ORG',
        ]);

        self::assertSame(
            'another.editor@example.org',
            BackendUtility::getRecord('tt_content', $contentId)['tx_testdatahandler_email_lower']
        );
    }

    #[Test]
    public function emailIsLowercasedBeforeUniquenessIsEvaluated(): void
    {
        $this->createContentRecord(['tx_testdatahandler_email_lower_unique' => 'editor@example.org']);

        $record = $this->createContentRecord(['tx_testdatahandler_email_lower_unique' => 'Editor@Example.ORG']);

        self::assertSame('editor@example.org0', $record['tx_testdatahandler_email_lower_unique']);
    }

    private function createContentRecord(array $fieldValues): array
    {
        return BackendUtility::getRecord('tt_content', $this->createContentId($fieldValues));
    }

    private function createContentId(array $fieldValues): int
    {
        $map = new ActionService()->createNewRecord('tt_content', self::PAGE_ID, $fieldValues);
        return (int)reset($map['tt_content']);
    }
}
