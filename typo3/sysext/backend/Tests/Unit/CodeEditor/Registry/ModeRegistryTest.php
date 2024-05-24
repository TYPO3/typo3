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

namespace TYPO3\CMS\Backend\Tests\Unit\CodeEditor\Registry;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\CodeEditor\Mode;
use TYPO3\CMS\Backend\CodeEditor\Registry\ModeRegistry;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Unit test class for Registry\ModeRegistry
 */
final class ModeRegistryTest extends UnitTestCase
{
    protected ModeRegistry $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new ModeRegistry();
    }

    #[Test]
    public function latestDefaultModeIsReturned(): void
    {
        $module = JavaScriptModuleInstruction::create('@test/foo', 'bar')->invoke();
        $firstDefaultMode = (new Mode($module))->setAsDefault();
        $expected = (new Mode($module))->setAsDefault();
        $this->subject->register($firstDefaultMode)->register($expected);
        $actual = $this->subject->getDefaultMode();

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function formatCodeReturnsCorrectMode(): void
    {
        $module = JavaScriptModuleInstruction::create('@test/mode', 'formatCode')->invoke();
        $expected = (new Mode($module))->setFormatCode('code');
        $this->subject->register($expected);
        $actual = $this->subject->getByFormatCode('code');

        self::assertSame($expected, $actual);
    }

    #[Test]
    public function modeIsFetchedByFileExtension(): void
    {
        $module = JavaScriptModuleInstruction::create('@test/mode', 'extension')->invoke();
        $expected = (new Mode($module))->bindToFileExtensions(['ext', 'fext']);
        $this->subject->register($expected);
        $actual = $this->subject->getByFileExtension('fext');

        self::assertSame($expected, $actual);
    }
}
