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

namespace TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php;

use TYPO3\CMS\Install\ExtensionScanner\Php\MatcherFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class MatcherFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function createAllThrowWithMissingClass()
    {
        $subject = new MatcherFactory();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1501415721);
        $configuration = [
            [
                'configurationFile' => 'foo',
            ]
        ];
        $subject->createAll($configuration);
    }

    /**
     * @test
     */
    public function createAllThrowsWithMissingConfiguration()
    {
        $subject = new MatcherFactory();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1501416365);
        $configuration = [
            [
                'class' => \stdClass::class,
            ]
        ];
        $subject->createAll($configuration);
    }

    /**
     * @test
     */
    public function createAllThrowsWithBothConfigurationFileAndConfigurationArray()
    {
        $subject = new MatcherFactory();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1501419367);
        $configuration = [
            [
                'class' => \stdClass::class,
                'configurationFile' => 'foo',
                'configurationArray' => [],
            ]
        ];
        $subject->createAll($configuration);
    }

    /**
     * @test
     */
    public function createAllThrowsIfConfigurationFileDoesNotExist()
    {
        $subject = new MatcherFactory();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1501509605);
        $configuration = [
            [
                'class' => \stdClass::class,
                'configurationFile' => 'EXT:install/does/not/exist.php',
            ]
        ];
        $subject->createAll($configuration);
    }

    /**
     * @test
     */
    public function createAllThrowsIfConfigurationFileDoesNotReturnArray()
    {
        $subject = new MatcherFactory();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1501509548);
        $configuration = [
            [
                'class' => \stdClass::class,
                'configurationFile' => 'EXT:install/Tests/Unit/ExtensionScanner/Php/Fixtures/MatcherFactoryInvalidConfigurationFileFixture.php',
            ]
        ];
        $subject->createAll($configuration);
    }

    /**
     * @test
     */
    public function createAllThrowsIfConfigurationArrayIsNotAnArray()
    {
        $subject = new MatcherFactory();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1501509738);
        $configuration = [
            [
                'class' => \stdClass::class,
                'configurationArray' => 'foo',
            ]
        ];
        $subject->createAll($configuration);
    }

    /**
     * @test
     */
    public function createAllThrowIfMatcherDoesNotImplementCodeScannerInterface()
    {
        $subject = new MatcherFactory();
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1501510168);
        $configuration = [
            [
                'class' => \stdClass::class,
                'configurationArray' => [],
            ]
        ];
        $subject->createAll($configuration);
    }
}
