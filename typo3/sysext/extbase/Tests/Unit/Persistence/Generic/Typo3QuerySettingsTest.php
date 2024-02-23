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

namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class Typo3QuerySettingsTest extends UnitTestCase
{
    protected ?Typo3QuerySettings $subject = null;

    /**
     * setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new Typo3QuerySettings(
            new Context(),
            $this->createMock(ConfigurationManagerInterface::class)
        );
    }

    public static function booleanValueProvider(): array
    {
        return [
            'TRUE setting' => [true],
            'FALSE setting' => [false],
        ];
    }

    public static function arrayValueProvider(): array
    {
        return [
            'empty array' => [[]],
            'two elements associative' => [
                [
                    'one' => '42',
                    21 => 12,
                ],
            ],
            'three elements' => [
                [
                    1,
                    'dummy',
                    [],
                ],
            ],
        ];
    }

    #[DataProvider('booleanValueProvider')]
    #[Test]
    public function setRespectStoragePageSetsRespectStoragePageCorrectly(bool $input): void
    {
        $this->subject->setRespectStoragePage($input);
        self::assertEquals($input, $this->subject->getRespectStoragePage());
    }

    #[Test]
    public function setRespectStoragePageAllowsChaining(): void
    {
        self::assertInstanceOf(QuerySettingsInterface::class, $this->subject->setRespectStoragePage(true));
    }

    #[DataProvider('arrayValueProvider')]
    #[Test]
    public function setStoragePageIdsSetsStoragePageIdsCorrectly(array $input): void
    {
        $this->subject->setStoragePageIds($input);
        self::assertEquals($input, $this->subject->getStoragePageIds());
    }

    #[Test]
    public function setStoragePageIdsAllowsChaining(): void
    {
        self::assertInstanceOf(QuerySettingsInterface::class, $this->subject->setStoragePageIds([1, 2, 3]));
    }

    #[DataProvider('booleanValueProvider')]
    #[Test]
    public function setRespectSysLanguageSetsRespectSysLanguageCorrectly(bool $input): void
    {
        $this->subject->setRespectSysLanguage($input);
        self::assertEquals($input, $this->subject->getRespectSysLanguage());
    }

    #[Test]
    public function setRespectSysLanguageAllowsChaining(): void
    {
        self::assertInstanceOf(QuerySettingsInterface::class, $this->subject->setRespectSysLanguage(true));
    }

    #[Test]
    public function setLanguageAspectHasFluentInterface(): void
    {
        self::assertSame($this->subject, $this->subject->setLanguageAspect(new LanguageAspect(1)));
    }

    #[DataProvider('booleanValueProvider')]
    #[Test]
    public function setIgnoreEnableFieldsSetsIgnoreEnableFieldsCorrectly(bool $input): void
    {
        $this->subject->setIgnoreEnableFields($input);
        self::assertEquals($input, $this->subject->getIgnoreEnableFields());
    }

    #[Test]
    public function setIgnoreEnableFieldsAllowsChaining(): void
    {
        self::assertInstanceOf(QuerySettingsInterface::class, $this->subject->setIgnoreEnableFields(true));
    }

    #[DataProvider('arrayValueProvider')]
    #[Test]
    public function setEnableFieldsToBeIgnoredSetsEnableFieldsToBeIgnoredCorrectly(array $input): void
    {
        $this->subject->setEnableFieldsToBeIgnored($input);
        self::assertEquals($input, $this->subject->getEnableFieldsToBeIgnored());
    }

    #[Test]
    public function setEnableFieldsToBeIgnoredAllowsChaining(): void
    {
        self::assertInstanceOf(
            QuerySettingsInterface::class,
            $this->subject->setEnableFieldsToBeIgnored(['starttime', 'endtime'])
        );
    }

    #[DataProvider('booleanValueProvider')]
    #[Test]
    public function setIncludeDeletedSetsIncludeDeletedCorrectly(bool $input): void
    {
        $this->subject->setIncludeDeleted($input);
        self::assertEquals($input, $this->subject->getIncludeDeleted());
    }

    #[Test]
    public function setIncludeDeletedAllowsChaining(): void
    {
        self::assertInstanceOf(QuerySettingsInterface::class, $this->subject->setIncludeDeleted(true));
    }
}
