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

namespace TYPO3\CMS\Backend\Tests\Functional\Configuration;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Processor\Placeholder\EnvPlaceholderProcessor;
use TYPO3\CMS\Core\Configuration\Processor\Placeholder\EnvVariableProcessor;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class EnvPlaceholderProcessorTest extends FunctionalTestCase
{
    #[Test]
    public function canProcessSingleEnv(): void
    {
        $context = getenv('TYPO3_CONTEXT');
        self::assertEquals('Testing', $context);

        $subject = GeneralUtility::makeInstance(EnvVariableProcessor::class);
        self::assertEquals('Testing', $subject->process('TYPO3_CONTEXT', []));
    }

    #[Test]
    public function canProcessEnvs(): void
    {
        $subject = GeneralUtility::makeInstance(EnvPlaceholderProcessor::class);
        self::assertEquals('Testing', $subject->process('%env(TYPO3_CONTEXT)%'));

        $subject = GeneralUtility::makeInstance(EnvPlaceholderProcessor::class);
        self::assertEquals('prefix Testing', $subject->process('prefix %env(TYPO3_CONTEXT)%'));

        $subject = GeneralUtility::makeInstance(EnvPlaceholderProcessor::class);
        self::assertEquals('Testing suffix', $subject->process('%env(TYPO3_CONTEXT)% suffix'));

        $subject = GeneralUtility::makeInstance(EnvPlaceholderProcessor::class);
        self::assertEquals('prefix Testing Testing suffix', $subject->process('prefix %env(TYPO3_CONTEXT)% %env(TYPO3_CONTEXT)% suffix'));
    }

    #[Test]
    public function failsOnInvalidSingleEnv(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $subject = GeneralUtility::makeInstance(EnvVariableProcessor::class);
        $result = $subject->process('MISSING', []);
        self::assertNull($result);
    }

    #[Test]
    public function doesNotPerformStringReplacementOnMissingEnvs(): void
    {
        $subject = GeneralUtility::makeInstance(EnvPlaceholderProcessor::class);
        $result = $subject->process('prefix %env(TYPO3_CONTEXT)% %env(TYPO3_missing)% suffix');
        self::assertEquals('prefix Testing %env(TYPO3_missing)% suffix', $result);
    }
}
