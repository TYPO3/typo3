<?php
declare(strict_types = 1);
namespace TYPO3\CMS\T3editor\Tests\Unit\Registry;

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
     * @var ModeRegistry|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = $this->getAccessibleMock(ModeRegistry::class, ['dummy'], [], '', false);
    }

    /**
     * @test
     */
    public function identifierIsReturned()
    {
        $expected = GeneralUtility::makeInstance(Mode::class, 'test/mode/default/default');
        $this->subject->register($expected);
        $actual = $this->subject->getByIdentifier('test/mode/default/default');

        static::assertSame($expected->getIdentifier(), $actual->getIdentifier());
    }

    /**
     * @test
     */
    public function latestDefaultModeIsReturned()
    {
        $firstDefaultMode = GeneralUtility::makeInstance(Mode::class, 'test/another/foo/bar')->setAsDefault();
        $expected = GeneralUtility::makeInstance(Mode::class, 'test/another/defaultmode/defaultmode')->setAsDefault();
        $this->subject->register($firstDefaultMode)->register($expected);
        $actual = $this->subject->getDefaultMode();

        static::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function formatCodeReturnsCorrectMode()
    {
        $expected = GeneralUtility::makeInstance(Mode::class, 'test/mode/format/code')->setFormatCode('code');
        $this->subject->register($expected);
        $actual = $this->subject->getByFormatCode('code');

        static::assertSame($expected, $actual);
    }

    /**
     * @test
     */
    public function modeIsFetchedByFileExtension()
    {
        $expected = GeneralUtility::makeInstance(Mode::class, 'test/mode/extension/extension')->bindToFileExtensions(['ext', 'fext']);
        $this->subject->register($expected);
        $actual = $this->subject->getByFileExtension('fext');

        static::assertSame($expected, $actual);
    }
}
