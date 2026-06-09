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

namespace TYPO3\CMS\Core\Tests\Functional\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class GeneralUtilityTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    public static function isAllowedAbsPathDataProvider(): iterable
    {
        yield '{{project-path}}' => ['{{project-path}}', true];
        yield '{{project-path}}/' => ['{{project-path}}/', true];
        yield '{{project-path}}/some-file.png' => ['{{project-path}}/', true];
        yield '{{project-path}}-other' => ['{{project-path}}-other', false];
        yield '{{project-path}}-other/' => ['{{project-path}}-other', false];
        yield '{{project-path}}-other/some-file.png' => ['{{project-path}}-other', false];
    }

    /**
     * See `\TYPO3\CMS\Core\Tests\Unit\Utility\PathUtilityTest::allowedAdditionalPathsAreEvaluated`
     * for the evaluation of `$GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath']`.
     */
    #[Test]
    #[DataProvider('isAllowedAbsPathDataProvider')]
    public function allowedAbsolutePathIsEvaluated(string $path, bool $expectation): void
    {
        $path = str_replace('{{project-path}}', Environment::getPublicPath(), $path);
        self::assertSame($expectation, GeneralUtility::isAllowedAbsPath($path));
    }
}
