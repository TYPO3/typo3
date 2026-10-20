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

namespace TYPO3\CMS\Linkvalidator\Tests\Functional\Linktype;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;
use TYPO3\CMS\Linkvalidator\Linktype\InternalLinktype;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class InternalLinktypeTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'linkvalidator',
        'info',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../../../core/Tests/Functional/Fixtures/be_users_admin.csv');
        $GLOBALS['BE_USER'] = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);
        $GLOBALS['EXEC_TIME'] = time();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/input_content_with_link_to_page_hidden_by_parent.csv');
    }

    public static function getErrorMessageNamesPageThatHidesTheLinkedPageDataProvider(): array
    {
        return [
            'page is hidden itself' => [
                2,
                'Page \'Hidden with subpages\' (id=2) is not visible.',
            ],
            'page is hidden by its parent page' => [
                3,
                'Page \'Below a hidden page with subpages\' (id=3) is not visible, because the page \'Hidden with subpages\' (id=2) hides its subpages.',
            ],
            'page is hidden by an expired grandparent page' => [
                8,
                'Page \'Two levels below an expired page with subpages\' (id=8) is not visible, because the page \'Expired with subpages\' (id=6) hides its subpages.',
            ],
        ];
    }

    #[DataProvider('getErrorMessageNamesPageThatHidesTheLinkedPageDataProvider')]
    #[Test]
    public function getErrorMessageNamesPageThatHidesTheLinkedPage(int $pageId, string $expectedMessage): void
    {
        $subject = $this->get(InternalLinktype::class);
        $softRefEntry = ['substr' => ['type' => 'db', 'recordRef' => 'pages:' . $pageId, 'tokenValue' => (string)$pageId]];

        self::assertFalse($subject->checkLink((string)$pageId, $softRefEntry, $this->get(LinkAnalyzer::class)));
        self::assertSame(InternalLinktype::HIDDEN, $subject->getErrorParams()['errorType']['page']);
        self::assertSame($expectedMessage, $subject->getErrorMessage($subject->getErrorParams()));
    }

    #[Test]
    public function getErrorMessageRendersErrorParamsStoredWithoutHidingPage(): void
    {
        $errorParams = [
            'errorType' => ['page' => InternalLinktype::HIDDEN],
            'page' => ['title' => 'Below a hidden page with subpages', 'uid' => 3],
        ];

        self::assertSame(
            'Page \'Below a hidden page with subpages\' (id=3) is not visible.',
            $this->get(InternalLinktype::class)->getErrorMessage($errorParams)
        );
    }
}
