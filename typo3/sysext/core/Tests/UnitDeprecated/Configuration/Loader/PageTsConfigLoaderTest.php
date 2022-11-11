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

namespace TYPO3\CMS\Core\Tests\UnitDeprecated\Configuration\Loader;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\Event\ModifyLoadedPageTsConfigEvent;
use TYPO3\CMS\Core\Configuration\Loader\PageTsConfigLoader;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PageTsConfigLoaderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function alwaysLoadDefaultSettings(): void
    {
        $expected = [
            'default' => $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'],
        ];
        $expectedString = implode('"\n[GLOBAL]\n"', $expected);
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $subject = new PageTsConfigLoader($eventDispatcherMock);
        $event = new ModifyLoadedPageTsConfigEvent($expected, []);
        $eventDispatcherMock->method('dispatch')->with(self::isInstanceOf(ModifyLoadedPageTsConfigEvent::class))->willReturn($event);
        $result = $subject->collect([]);
        self::assertSame($expected, $result);

        $result = $subject->load([]);
        self::assertSame($expectedString, $result);
    }

    /**
     * @test
     */
    public function loadDefaultSettingsAtTheBeginningAndKeepEmptyEntriesExpectUidZero(): void
    {
        $expected = [
            'default' => $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'],
            'page_13' => 'waiting for = love',
            'page_27' => '',
        ];
        $rootLine = [['uid' => 0, 'pid' => 0], ['uid' => 13, 'TSconfig' => 'waiting for = love'], ['uid' => 27, 'TSconfig' => '']];
        $eventDispatcherMock = $this->createMock(EventDispatcherInterface::class);
        $event = new ModifyLoadedPageTsConfigEvent($expected, $rootLine);
        $eventDispatcherMock->method('dispatch')->with(self::isInstanceOf(ModifyLoadedPageTsConfigEvent::class))->willReturn($event);
        $subject = new PageTsConfigLoader($eventDispatcherMock);
        $result = $subject->collect($rootLine);
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function loadExternalInclusionsCorrectlyAndKeepLoadingOrder(): void
    {
        $expected = [
            'global' => '',
            'default' => $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'],
            'page_13_includes_0' => 'Show_me = more
',
            'page_13' => 'waiting for = love',
            'page_27' => '',
        ];
        $rootLine = [['uid' => 13, 'TSconfig' => 'waiting for = love', 'tsconfig_includes' => 'EXT:core/Tests/UnitDeprecated/Configuration/Loader/Fixtures/included.typoscript'], ['uid' => 27, 'TSconfig' => '']];
        $subject = new PageTsConfigLoader(new NoopEventDispatcher());
        $result = $subject->collect($rootLine);
        self::assertSame($expected, $result);
    }

    /**
     * @test
     */
    public function invalidExternalFileIsNotLoaded(): void
    {
        $expected = [
            'global' => '',
            'default' => $GLOBALS['TYPO3_CONF_VARS']['BE']['defaultPageTSconfig'],
            'page_13' => 'waiting for = love',
            'page_27' => '',
        ];
        $expectedString = implode("\n[GLOBAL]\n", $expected);
        $rootLine = [['uid' => 13, 'TSconfig' => 'waiting for = love', 'tsconfig_includes' => 'EXT:core/Tests/UnitDeprecated/Configuration/Loader/Fixtures/me_does_not_exist.typoscript'], ['uid' => 27, 'TSconfig' => '']];
        $subject = new PageTsConfigLoader(new NoopEventDispatcher());
        $result = $subject->collect($rootLine);
        self::assertSame($expected, $result);

        $result = $subject->load($rootLine);
        self::assertSame($expectedString, $result);
    }
}
