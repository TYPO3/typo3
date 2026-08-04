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
use TYPO3\CMS\Core\Context\Context;
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
        $eventDispatcher = new class implements EventDispatcherInterface {
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

    public static function simulatedTimeIsRoundedUpToFullMinutesDataProvider(): array
    {
        return [
            'starttime with seconds within the current minute simulates the next full minute' => [
                mktime(9, 13, 45, 8, 4, 2026),
                mktime(9, 13, 21, 8, 4, 2026),
                mktime(9, 14, 0, 8, 4, 2026),
            ],
            'starttime with seconds in the future simulates the next full minute' => [
                mktime(9, 12, 0, 8, 4, 2026),
                mktime(9, 13, 21, 8, 4, 2026),
                mktime(9, 14, 0, 8, 4, 2026),
            ],
            'starttime on a full minute in the future is simulated unchanged' => [
                mktime(9, 13, 45, 8, 4, 2026),
                mktime(9, 14, 0, 8, 4, 2026),
                mktime(9, 14, 0, 8, 4, 2026),
            ],
            'starttime on a full minute in the past is not simulated' => [
                mktime(9, 13, 45, 8, 4, 2026),
                mktime(9, 13, 0, 8, 4, 2026),
                null,
            ],
            'starttime with seconds covered by the current access time is not simulated' => [
                mktime(9, 14, 10, 8, 4, 2026),
                mktime(9, 13, 21, 8, 4, 2026),
                null,
            ],
            'unset starttime is not simulated' => [
                mktime(9, 13, 45, 8, 4, 2026),
                0,
                null,
            ],
        ];
    }

    #[DataProvider('simulatedTimeIsRoundedUpToFullMinutesDataProvider')]
    #[Test]
    public function simulatedTimeIsRoundedUpToFullMinutes(int $currentTime, int $startTime, ?int $expectedSimulatedTime): void
    {
        $GLOBALS['EXEC_TIME'] = $currentTime;
        $pageInfo = ['uid' => 1, 'starttime' => $startTime, 'endtime' => 0];

        $parameters = PreviewUriBuilder::getAdditionalQueryParametersForAccessRestrictedPages($pageInfo, new Context(), [$pageInfo]);

        self::assertSame($expectedSimulatedTime, $parameters['ADMCMD_simTime'] ?? null);
    }

    #[Test]
    public function simulatedTimeIsAppliedToDateAspectWithinPageRepositoryPrecision(): void
    {
        $GLOBALS['EXEC_TIME'] = mktime(9, 13, 45, 8, 4, 2026);
        $startTime = mktime(9, 13, 21, 8, 4, 2026);
        $context = new Context();
        $pageInfo = ['uid' => 1, 'starttime' => $startTime, 'endtime' => 0];

        PreviewUriBuilder::getAdditionalQueryParametersForAccessRestrictedPages($pageInfo, $context, [$pageInfo]);

        // PageRepository builds its 'starttime' constraint from this very property, which is floored to 60 seconds
        self::assertGreaterThanOrEqual($startTime, $context->getPropertyFromAspect('date', 'accessTime'));
    }

    #[Test]
    public function simulatedTimeForEndTimeStaysOneSecondBeforeExpiration(): void
    {
        $GLOBALS['EXEC_TIME'] = mktime(9, 13, 45, 8, 4, 2026);
        $endTime = mktime(9, 12, 30, 8, 4, 2026);
        $context = new Context();
        $pageInfo = ['uid' => 1, 'starttime' => 0, 'endtime' => $endTime];

        $parameters = PreviewUriBuilder::getAdditionalQueryParametersForAccessRestrictedPages($pageInfo, $context, [$pageInfo]);

        self::assertSame($endTime - 1, $parameters['ADMCMD_simTime']);
        self::assertLessThan($endTime, $context->getPropertyFromAspect('date', 'accessTime'));
    }
}
