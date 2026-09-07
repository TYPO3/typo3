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

namespace TYPO3\CMS\Core\Tests\Functional\TypoScript;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteSettings;
use TYPO3\CMS\Core\Tests\Functional\Fixtures\Log\DummyWriter;
use TYPO3\CMS\Core\TypoScript\PageTsConfigFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Site settings used as constants within page TSconfig conditions.
 */
final class PageTsConfigFactoryConditionConstantTest extends FunctionalTestCase
{
    /**
     * The default FileWriter of an unconfigured instance persists WARNING and above.
     */
    private const array LEVELS_WRITTEN_BY_DEFAULT = [
        LogLevel::EMERGENCY,
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::ERROR,
        LogLevel::WARNING,
    ];
    protected array $configurationToUseInTestInstance = [
        'LOG' => [
            'TYPO3' => [
                'CMS' => [
                    'Core' => [
                        'TypoScript' => [
                            'writerConfiguration' => [
                                LogLevel::DEBUG => [
                                    DummyWriter::class => [],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        DummyWriter::$logs = [];
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        DummyWriter::$logs = [];
    }

    private function createForRootLineTsConfig(string $tsConfig, ?SiteSettings $siteSettings): array
    {
        $rootLine = [
            [
                'uid' => 1,
                'TSconfig' => $tsConfig,
            ],
        ];
        $site = $siteSettings === null ? new NullSite() : new Site('siteIdentifier', 1, [], $siteSettings);
        return $this->get(PageTsConfigFactory::class)->create($rootLine, $site)->getConditionListWithVerdicts();
    }

    private function getLogsWrittenByDefault(): array
    {
        $logs = [];
        foreach (DummyWriter::$logs as $log) {
            if (in_array($log->getLevel(), self::LEVELS_WRITTEN_BY_DEFAULT, true)) {
                $logs[] = $log->getLevel() . ': ' . ($log->getData()['expression'] ?? '');
            }
        }
        return $logs;
    }

    #[Test]
    public function definedSiteSettingIsSubstitutedInCondition(): void
    {
        $verdicts = $this->createForRootLineTsConfig(
            '[1 == {$aSiteSetting}]' . chr(10) . 'matched = yes' . chr(10) . '[END]',
            SiteSettings::createFromSettingsTree(['aSiteSetting' => 1]),
        );
        self::assertTrue($verdicts['1 == 1'] ?? null);
        self::assertSame([], $this->getLogsWrittenByDefault());
    }

    #[Test]
    public function undefinedSiteSettingInConditionDoesNotLogAnError(): void
    {
        $verdicts = $this->createForRootLineTsConfig(
            '[1 == {$anUndefinedSetting}]' . chr(10) . 'matched = yes' . chr(10) . '[END]',
            SiteSettings::createFromSettingsTree(['aSiteSetting' => 1]),
        );
        self::assertFalse($verdicts['1 == {$anUndefinedSetting}'] ?? null);
        self::assertSame([], $this->getLogsWrittenByDefault());
    }

    #[Test]
    public function siteSettingInConditionWithoutSiteDoesNotLogAnError(): void
    {
        $verdicts = $this->createForRootLineTsConfig(
            '[1 == {$aSiteSetting}]' . chr(10) . 'matched = yes' . chr(10) . '[END]',
            null,
        );
        self::assertFalse($verdicts['1 == {$aSiteSetting}'] ?? null);
        self::assertSame([], $this->getLogsWrittenByDefault());
    }

    #[Test]
    public function syntacticallyBrokenConditionIsStillLoggedAsError(): void
    {
        $this->createForRootLineTsConfig(
            '[1 === ===]' . chr(10) . 'matched = yes' . chr(10) . '[END]',
            null,
        );
        self::assertSame(['error: 1 === ==='], $this->getLogsWrittenByDefault());
    }
}
