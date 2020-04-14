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

namespace TYPO3\CMS\Install\Tests\Unit\ExtensionScanner\Php\Matcher;

use TYPO3\CMS\Install\ExtensionScanner\Php\Matcher\AbstractCoreMatcher;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AbstractCoreMatcherTest extends UnitTestCase
{
    /**
     * @test
     */
    public function validateMatcherDefinitionsRunsFineWithProperDefinition()
    {
        $matcher = $this->getAccessibleMockForAbstractClass(AbstractCoreMatcher::class, [], '', false);
        $configuration = [
            'foo/bar->baz' => [
                'requiredArg1' => 42,
                'restFiles' => [
                    'aRest.rst',
                ],
            ],
        ];
        $matcher->_set('matcherDefinitions', $configuration);
        $matcher->_call('validateMatcherDefinitions', ['requiredArg1']);
    }

    /**
     * @test
     */
    public function validateMatcherDefinitionsThrowsIfRequiredArgIsNotInConfig()
    {
        $matcher = $this->getAccessibleMockForAbstractClass(AbstractCoreMatcher::class, [], '', false);
        $configuration = [
            'foo/bar->baz' => [
                'someNotRequiredConfig' => '',
                'restFiles' => [
                    'aRest.rst',
                ],
            ],
        ];
        $matcher->_set('matcherDefinitions', $configuration);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1500492001);
        $matcher->_call('validateMatcherDefinitions', ['requiredArg1']);
    }

    /**
     * @test
     */
    public function validateMatcherDefinitionsThrowsWithMissingRestFiles()
    {
        $matcher = $this->getAccessibleMockForAbstractClass(AbstractCoreMatcher::class, [], '', false);
        $configuration = [
            'foo/bar->baz' => [
                'restFiles' => [],
            ],
        ];
        $matcher->_set('matcherDefinitions', $configuration);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1500496068);
        $matcher->_call('validateMatcherDefinitions', []);
    }

    /**
     * @test
     */
    public function validateMatcherDefinitionsThrowsWithEmptySingleRestFile()
    {
        $matcher = $this->getAccessibleMockForAbstractClass(AbstractCoreMatcher::class, [], '', false);
        $configuration = [
            'foo/bar->baz' => [
                'restFiles' => [
                    'foo.rst',
                    '',
                ],
            ],
        ];
        $matcher->_set('matcherDefinitions', $configuration);
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1500735983);
        $matcher->_call('validateMatcherDefinitions', []);
    }

    /**
     * @test
     */
    public function initializeMethodNameArrayThrowsWithInvalidKeys()
    {
        $matcher = $this->getAccessibleMockForAbstractClass(AbstractCoreMatcher::class, [], '', false);
        $configuration = [
            'no\method\given' => [
                'restFiles' => [],
            ],
        ];
        $matcher->_set('matcherDefinitions', $configuration);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1500557309);
        $matcher->_call('initializeFlatMatcherDefinitions');
    }
}
