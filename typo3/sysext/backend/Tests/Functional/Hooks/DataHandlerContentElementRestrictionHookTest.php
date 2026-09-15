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

namespace TYPO3\CMS\Backend\Tests\Functional\Hooks;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DataHandlerContentElementRestrictionHookTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
    }

    public static function newRecordsAreCheckedAgainstContentElementRestrictionsOfTheirPageDataProvider(): \Generator
    {
        yield 'allowed CType on existing page is created' => [
            'pages' => [],
            'contentElements' => ['NEW10' => ['pid' => 1, 'CType' => 'text', 'colPos' => 0]],
            'expectedNumberOfContentElements' => 1,
        ];
        yield 'disallowed CType on existing page is rejected' => [
            'pages' => [],
            'contentElements' => ['NEW10' => ['pid' => 1, 'CType' => 'html', 'colPos' => 0]],
            'expectedNumberOfContentElements' => 0,
        ];
        yield 'allowed CType on new page is created' => [
            'pages' => ['NEW1' => ['pid' => 1, 'title' => 'New page', 'backend_layout' => 'pagets__restricted']],
            'contentElements' => ['NEW10' => ['pid' => 'NEW1', 'CType' => 'text', 'colPos' => 0]],
            'expectedNumberOfContentElements' => 1,
        ];
        yield 'disallowed CType on new page is rejected' => [
            'pages' => ['NEW1' => ['pid' => 1, 'title' => 'New page', 'backend_layout' => 'pagets__restricted']],
            'contentElements' => ['NEW10' => ['pid' => 'NEW1', 'CType' => 'html', 'colPos' => 0]],
            'expectedNumberOfContentElements' => 0,
        ];
        yield 'CType disallowed on the parent page is created on new page without backend layout' => [
            'pages' => ['NEW1' => ['pid' => 1, 'title' => 'New page', 'backend_layout' => '-1']],
            'contentElements' => ['NEW10' => ['pid' => 'NEW1', 'CType' => 'html', 'colPos' => 0]],
            'expectedNumberOfContentElements' => 1,
        ];
        yield 'disallowed CType on new page inheriting the backend layout is rejected' => [
            'pages' => ['NEW1' => ['pid' => 2, 'title' => 'New page']],
            'contentElements' => ['NEW10' => ['pid' => 'NEW1', 'CType' => 'html', 'colPos' => 0]],
            'expectedNumberOfContentElements' => 0,
        ];
        yield 'disallowed CType placed after a new content element is rejected' => [
            'pages' => [],
            'contentElements' => [
                'NEW10' => ['pid' => 1, 'CType' => 'text', 'colPos' => 0],
                'NEW11' => ['pid' => '-NEW10', 'CType' => 'html', 'colPos' => 0],
            ],
            'expectedNumberOfContentElements' => 1,
        ];
    }

    #[DataProvider('newRecordsAreCheckedAgainstContentElementRestrictionsOfTheirPageDataProvider')]
    #[Test]
    public function newRecordsAreCheckedAgainstContentElementRestrictionsOfTheirPage(array $pages, array $contentElements, int $expectedNumberOfContentElements): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(array_filter(['pages' => $pages, 'tt_content' => $contentElements]), []);
        $dataHandler->process_datamap();

        foreach (array_keys($pages) as $pagePlaceholder) {
            self::assertArrayHasKey($pagePlaceholder, $dataHandler->substNEWwithIDs);
        }
        $numberOfContentElements = $this->get(ConnectionPool::class)->getConnectionForTable('tt_content')->count('*', 'tt_content', []);
        self::assertSame($expectedNumberOfContentElements, $numberOfContentElements);
        if ($expectedNumberOfContentElements === count($contentElements)) {
            self::assertSame([], $dataHandler->errorLog);
        } else {
            self::assertStringContainsString('due to disallowed value(s)', implode(chr(10), $dataHandler->errorLog));
        }
    }

    #[Test]
    public function existingRecordsAreCheckedAgainstContentElementRestrictions(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tt_content.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['tt_content' => [1 => ['CType' => 'html']]], []);
        $dataHandler->process_datamap();

        $cType = $this->get(ConnectionPool::class)->getConnectionForTable('tt_content')->select(['CType'], 'tt_content', ['uid' => 1])->fetchOne();
        self::assertSame('text', $cType);
        self::assertStringContainsString('due to disallowed value(s)', implode(chr(10), $dataHandler->errorLog));
    }

    #[Test]
    public function newRecordsOnNewPageInWorkspaceAreCheckedAgainstContentElementRestrictions(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/sys_workspace.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $backendUser->workspace = 1;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([
            'pages' => ['NEW1' => ['pid' => 2, 'title' => 'New page']],
            'tt_content' => [
                'NEW10' => ['pid' => 'NEW1', 'CType' => 'text', 'colPos' => 0],
                'NEW11' => ['pid' => 'NEW1', 'CType' => 'html', 'colPos' => 0],
            ],
        ], []);
        $dataHandler->process_datamap();

        self::assertArrayHasKey('NEW1', $dataHandler->substNEWwithIDs);
        self::assertArrayHasKey('NEW10', $dataHandler->substNEWwithIDs);
        self::assertArrayNotHasKey('NEW11', $dataHandler->substNEWwithIDs);
        self::assertStringContainsString('due to disallowed value(s)', implode(chr(10), $dataHandler->errorLog));
    }
}
