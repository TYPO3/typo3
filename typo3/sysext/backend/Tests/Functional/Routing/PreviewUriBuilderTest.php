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

namespace TYPO3\CMS\Backend\Tests\Functional\Routing;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Backend\Routing\Event\BeforePagePreviewUriGeneratedEvent;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PreviewUriBuilderTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages_preview.csv');
        $GLOBALS['TCA']['tx_custom_table'] = [];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
    }

    #[Test]
    public function attributesContainAlternativeUri(): void
    {
        $eventDispatcher = new class () implements EventDispatcherInterface {
            public function dispatch(object $event)
            {
                if ($event instanceof BeforePagePreviewUriGeneratedEvent) {
                    $alternativeUri = 'https://typo3.org/about/typo3-the-cms/the-history-of-typo3/#section';
                    $event->setPreviewUri(new Uri($alternativeUri));
                }
                return $event;
            }
        };
        GeneralUtility::addInstance(EventDispatcherInterface::class, $eventDispatcher);
        $subject = PreviewUriBuilder::create(0)->withModuleLoading(false);
        $attributes = $subject->buildDispatcherAttributes([PreviewUriBuilder::OPTION_SWITCH_FOCUS => false]);

        self::assertSame(
            [
                'data-dispatch-action' => 'TYPO3.WindowManager.localOpen',
                'data-dispatch-args' => '["https:\/\/typo3.org\/about\/typo3-the-cms\/the-history-of-typo3\/#section",false,"newTYPO3frontendWindow"]',
            ],
            $attributes
        );
    }

    public static function isPreviewableWorksForPageRecordsDataProvider(): array
    {
        return [
            'standard page with valid doktype' => [['uid' => 1, 'doktype' => 1], true],
            'page with sysfolder doktype' => [['uid' => 1, 'doktype' => PageRepository::DOKTYPE_SYSFOLDER], false],
            'page with spacer doktype' => [['uid' => 1, 'doktype' => PageRepository::DOKTYPE_SPACER], false],
            'page with zero doktype' => [['uid' => 1, 'doktype' => 0], false],
            'page with negative doktype' => [['uid' => 1, 'doktype' => -1], false],
            'page with missing doktype' => [['uid' => 1], false],
            'page with delete placeholder' => [['uid' => 1, 'doktype' => 1, 't3ver_state' => VersionState::DELETE_PLACEHOLDER->value], false],
            'page with missing version state' => [['uid' => 1, 'doktype' => 1], true],
        ];
    }

    #[DataProvider('isPreviewableWorksForPageRecordsDataProvider')]
    #[Test]
    public function isPreviewableWorksForPageRecords(array $pageRecord, bool $expected): void
    {
        $subject = PreviewUriBuilder::create($pageRecord);
        self::assertSame($expected, $subject->isPreviewable());
    }

    #[Test]
    public function isPreviewableReturnsFalseForZeroPageId(): void
    {
        $subject = PreviewUriBuilder::create(0);
        self::assertFalse($subject->isPreviewable());
    }

    #[Test]
    public function isPreviewableReturnsFalseForEmptyRecord(): void
    {
        $subject = PreviewUriBuilder::create([]);
        self::assertFalse($subject->isPreviewable());
    }

    #[Test]
    public function isPreviewableReturnsTrueForTtContentRecord(): void
    {
        $contentRecord = ['uid' => 1];
        $subject = PreviewUriBuilder::createForRecordPreview('tt_content', $contentRecord, 1);
        self::assertTrue($subject->isPreviewable());
    }

    #[Test]
    public function isPreviewableReturnsFalseForTtContentRecordOnSysfolderPage(): void
    {
        $contentRecord = ['uid' => 1];
        $subject = PreviewUriBuilder::createForRecordPreview('tt_content', $contentRecord, 2);
        self::assertFalse($subject->isPreviewable());
    }

    #[Test]
    public function isPreviewableReturnsFalseForCustomTableWithoutTSconfig(): void
    {
        $customRecord = ['uid' => 1];
        $subject = PreviewUriBuilder::createForRecordPreview('tx_custom_table', $customRecord, 1);
        self::assertFalse($subject->isPreviewable());
    }

    #[Test]
    public function isPreviewableReturnsTrueForCustomTableWithTSconfig(): void
    {
        $customRecord = ['uid' => 1];
        $subject = PreviewUriBuilder::createForRecordPreview('tx_custom_table', $customRecord, 4);
        self::assertTrue($subject->isPreviewable());
    }

    #[Test]
    public function isPreviewableReturnsFalseForCustomTableRecordWithSysfolderPage(): void
    {
        $customRecord = ['uid' => 1];
        $subject = PreviewUriBuilder::createForRecordPreview('tx_custom_table', $customRecord, 5);
        self::assertFalse($subject->isPreviewable());
    }

    #[Test]
    public function isPreviewableRespectsCustomTSconfigDisableButtonForDokType(): void
    {
        $pageRecord = ['uid' => 6, 'doktype' => 1];
        $subject = PreviewUriBuilder::create($pageRecord);
        self::assertFalse($subject->isPreviewable());
    }

    #[Test]
    public function isPreviewableAllowsSysfolderWhenTSconfigOverridesDefaults(): void
    {
        $pageRecord = ['uid' => 7, 'doktype' => PageRepository::DOKTYPE_SYSFOLDER];
        $subject = PreviewUriBuilder::create($pageRecord);
        self::assertTrue($subject->isPreviewable());
    }

    #[Test]
    public function isPreviewableAllowsMultipleDokTypesInTSconfigDisable(): void
    {
        $pageRecord = ['uid' => 8, 'doktype' => 2];
        $subject = PreviewUriBuilder::create($pageRecord);
        self::assertFalse($subject->isPreviewable());
    }

    #[Test]
    public function isPreviewableAllowsDokTypeNotInTSconfigDisableList(): void
    {
        $pageRecord = ['uid' => 8, 'doktype' => 4];
        $subject = PreviewUriBuilder::create($pageRecord);
        self::assertTrue($subject->isPreviewable());
    }
}
