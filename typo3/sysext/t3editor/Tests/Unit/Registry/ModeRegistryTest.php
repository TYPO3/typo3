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

namespace TYPO3\CMS\T3editor\Tests\Unit\Registry;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\T3editor\Mode;
use TYPO3\CMS\T3editor\Registry\ModeRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Unit test class for Registry\ModeRegistry
 */
class ModeRegistryTest extends UnitTestCase
{
    /**
     * @var ModeRegistry
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new ModeRegistry();
    }

    /**
     * @test
     */
    public function identifierIsReturned(): void
    {
        $expected = GeneralUtility::makeInstance(Mode::class, 'test/mode/default/default');
        $this->subject->register($expected);
        $actual = $this->subject->getByIdentifier('test/mode/default/default');

        self::assertSame($expected->getIdentifier(), $actual->getIdentifier());
    }

    /**
     * @test
     */
    public function latestDefaultModeIsReturned(): void
    {
        $firstDefaultMode = GeneralUtility::makeInstance(Mode::class, 'test/another/foo/bar')->setAsDefault();
        $expected = GeneralUtility::makeInstance(Mode::class, 'test/another/defaultmode/defaultmode')->setAsDefault();
        $this->subject->register($firstDefaultMode)->register($expected);
        $actual = $this->subject->getDefaultMode();

        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function formatCodeReturnsCorrectMode(): void
    {
        $expected = GeneralUtility::makeInstance(Mode::class, 'test/mode/format/code')->setFormatCode('code');
        $this->subject->register($expected);
        $actual = $this->subject->getByFormatCode('code');

        self::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function modeIsFetchedByFileExtension(): void
    {
        $expected = GeneralUtility::makeInstance(Mode::class, 'test/mode/extension/extension')->bindToFileExtensions(['ext', 'fext']);
        $this->subject->register($expected);
        $actual = $this->subject->getByFileExtension('fext');

        self::assertSame($expected, $actual);
    }
}
