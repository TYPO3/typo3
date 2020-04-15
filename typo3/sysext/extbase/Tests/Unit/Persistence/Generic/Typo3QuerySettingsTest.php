<?php

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

use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class Typo3QuerySettingsTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings
     */
    protected $typo3QuerySettings;

    /**
     * setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->typo3QuerySettings = $this->getAccessibleMock(Typo3QuerySettings::class, ['dummy']);
    }

    /**
     * @return array
     */
    public function booleanValueProvider()
    {
        return [
            'TRUE setting' => [true],
            'FALSE setting' => [false]
        ];
    }

    /**
     * @return array
     */
    public function arrayValueProvider()
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
    public function setRespectStoragePageSetsRespectStoragePageCorrectly($input)
    {
        $this->typo3QuerySettings->setRespectStoragePage($input);
        self::assertEquals($input, $this->typo3QuerySettings->getRespectStoragePage());
    }

    /**
     * @test
     */
    public function setRespectStoragePageAllowsChaining()
    {
        self::assertTrue($this->typo3QuerySettings->setRespectStoragePage(true) instanceof QuerySettingsInterface);
    }

    /**
     * @test
     * @dataProvider arrayValueProvider
     *
     * @param array $input
     */
    public function setStoragePageIdsSetsStoragePageIdsCorrectly($input)
    {
        $this->typo3QuerySettings->setStoragePageIds($input);
        self::assertEquals($input, $this->typo3QuerySettings->getStoragePageIds());
    }

    /**
     * @test
     */
    public function setStoragePageIdsAllowsChaining()
    {
        self::assertTrue($this->typo3QuerySettings->setStoragePageIds([1, 2, 3]) instanceof QuerySettingsInterface);
    }

    /**
     * @test
     * @dataProvider booleanValueProvider
     *
     * @param bool $input
     */
    public function setRespectSysLanguageSetsRespectSysLanguageCorrectly($input)
    {
        $this->typo3QuerySettings->setRespectSysLanguage($input);
        self::assertEquals($input, $this->typo3QuerySettings->getRespectSysLanguage());
    }

    /**
     * @test
     */
    public function setRespectSysLanguageAllowsChaining()
    {
        self::assertTrue($this->typo3QuerySettings->setRespectSysLanguage(true) instanceof QuerySettingsInterface);
    }

    /**
     * @test
     */
    public function setLanguageUidAllowsChaining()
    {
        self::assertTrue($this->typo3QuerySettings->setLanguageUid(42) instanceof QuerySettingsInterface);
    }

    /**
     * @test
     * @dataProvider booleanValueProvider
     *
     * @param bool $input
     */
    public function setIgnoreEnableFieldsSetsIgnoreEnableFieldsCorrectly($input)
    {
        $this->typo3QuerySettings->setIgnoreEnableFields($input);
        self::assertEquals($input, $this->typo3QuerySettings->getIgnoreEnableFields());
    }

    /**
     * @test
     */
    public function setIgnoreEnableFieldsAllowsChaining()
    {
        self::assertTrue($this->typo3QuerySettings->setIgnoreEnableFields(true) instanceof QuerySettingsInterface);
    }

    /**
     * @test
     * @dataProvider arrayValueProvider
     *
     * @param array $input
     */
    public function setEnableFieldsToBeIgnoredSetsEnableFieldsToBeIgnoredCorrectly($input)
    {
        $this->typo3QuerySettings->setEnableFieldsToBeIgnored($input);
        self::assertEquals($input, $this->typo3QuerySettings->getEnableFieldsToBeIgnored());
    }

    /**
     * @test
     */
    public function setEnableFieldsToBeIgnoredAllowsChaining()
    {
        self::assertTrue($this->typo3QuerySettings->setEnableFieldsToBeIgnored(['starttime', 'endtime']) instanceof QuerySettingsInterface);
    }

    /**
     * @test
     * @dataProvider booleanValueProvider
     *
     * @param bool $input
     */
    public function setIncludeDeletedSetsIncludeDeletedCorrectly($input)
    {
        $this->typo3QuerySettings->setIncludeDeleted($input);
        self::assertEquals($input, $this->typo3QuerySettings->getIncludeDeleted());
    }

    /**
     * @test
     */
    public function setIncludeDeletedAllowsChaining()
    {
        self::assertTrue($this->typo3QuerySettings->setIncludeDeleted(true) instanceof QuerySettingsInterface);
    }
}
