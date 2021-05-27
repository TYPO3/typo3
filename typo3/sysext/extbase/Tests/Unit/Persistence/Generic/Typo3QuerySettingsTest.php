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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class Typo3QuerySettingsTest extends UnitTestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    protected ?Typo3QuerySettings $subject = null;

    /**
     * setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new Typo3QuerySettings(
            new Context(),
            $this->prophesize(ConfigurationManagerInterface::class)->reveal()
        );
    }

    public function booleanValueProvider(): array
    {
        return [
            'TRUE setting' => [true],
            'FALSE setting' => [false]
        ];
    }

    public function arrayValueProvider(): array
    {
        return [
            'empty array' => [[]],
            'two elements associative' => [
                [
                    'one' => '42',
                    21 => 12
                ]
            ],
            'three elements' => [
                [
                    1,
                    'dummy',
                    []
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider booleanValueProvider
     * @param bool $input
     */
    public function setRespectStoragePageSetsRespectStoragePageCorrectly(bool $input): void
    {
        $this->subject->setRespectStoragePage($input);
        self::assertEquals($input, $this->subject->getRespectStoragePage());
    }

    /**
     * @test
     */
    public function setRespectStoragePageAllowsChaining(): void
    {
        self::assertTrue($this->subject->setRespectStoragePage(true) instanceof QuerySettingsInterface);
    }

    /**
     * @test
     * @dataProvider arrayValueProvider
     * @param array $input
     */
    public function setStoragePageIdsSetsStoragePageIdsCorrectly(array $input): void
    {
        $this->subject->setStoragePageIds($input);
        self::assertEquals($input, $this->subject->getStoragePageIds());
    }

    /**
     * @test
     */
    public function setStoragePageIdsAllowsChaining(): void
    {
        self::assertTrue($this->subject->setStoragePageIds([1, 2, 3]) instanceof QuerySettingsInterface);
    }

    /**
     * @test
     * @dataProvider booleanValueProvider
     * @param bool $input
     */
    public function setRespectSysLanguageSetsRespectSysLanguageCorrectly(bool $input): void
    {
        $this->subject->setRespectSysLanguage($input);
        self::assertEquals($input, $this->subject->getRespectSysLanguage());
    }

    /**
     * @test
     */
    public function setRespectSysLanguageAllowsChaining(): void
    {
        self::assertTrue($this->subject->setRespectSysLanguage(true) instanceof QuerySettingsInterface);
    }

    /**
     * @test
     */
    public function setLanguageUidAllowsChaining(): void
    {
        self::assertTrue($this->subject->setLanguageUid(42) instanceof QuerySettingsInterface);
    }

    /**
     * @test
     * @dataProvider booleanValueProvider
     * @param bool $input
     */
    public function setIgnoreEnableFieldsSetsIgnoreEnableFieldsCorrectly(bool $input): void
    {
        $this->subject->setIgnoreEnableFields($input);
        self::assertEquals($input, $this->subject->getIgnoreEnableFields());
    }

    /**
     * @test
     */
    public function setIgnoreEnableFieldsAllowsChaining(): void
    {
        self::assertTrue($this->subject->setIgnoreEnableFields(true) instanceof QuerySettingsInterface);
    }

    /**
     * @test
     * @dataProvider arrayValueProvider
     * @param array $input
     */
    public function setEnableFieldsToBeIgnoredSetsEnableFieldsToBeIgnoredCorrectly(array $input): void
    {
        $this->subject->setEnableFieldsToBeIgnored($input);
        self::assertEquals($input, $this->subject->getEnableFieldsToBeIgnored());
    }

    /**
     * @test
     */
    public function setEnableFieldsToBeIgnoredAllowsChaining(): void
    {
        self::assertTrue($this->subject->setEnableFieldsToBeIgnored(['starttime', 'endtime']) instanceof QuerySettingsInterface);
    }

    /**
     * @test
     * @dataProvider booleanValueProvider
     * @param bool $input
     */
    public function setIncludeDeletedSetsIncludeDeletedCorrectly(bool $input): void
    {
        $this->subject->setIncludeDeleted($input);
        self::assertEquals($input, $this->subject->getIncludeDeleted());
    }

    /**
     * @test
     */
    public function setIncludeDeletedAllowsChaining(): void
    {
        self::assertTrue($this->subject->setIncludeDeleted(true) instanceof QuerySettingsInterface);
    }
}
