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

namespace TYPO3\CMS\PHPStan\Tests\Rules\Classes\NamedArgumentUsageRule;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use TYPO3\CMS\PHPStan\Rules\Classes\NamedArgumentUsageRule;

final class NamedArgumentUsageRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NamedArgumentUsageRule(self::createReflectionProvider());
    }

    public function testRule(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/NamedArgumentFixture.php'], [
            [
                'Method call TYPO3\CMS\PHPStan\Tests\Rules\Classes\NamedArgumentUsageRule\Fixtures\NamedArgumentFixture::targetMethod() uses named arguments.',
                36,
            ],
            [
                'Method call TYPO3\CMS\PHPStan\Tests\Rules\Classes\NamedArgumentUsageRule\Fixtures\NamedArgumentFixture::targetMethod() uses named arguments. It skips the following optional parameters: p3, p4.',
                45,
            ],
            [
                'Method call TYPO3\CMS\PHPStan\Tests\Rules\Classes\NamedArgumentUsageRule\Fixtures\NamedArgumentFixture::targetMethod() uses named arguments. It skips the following optional parameters: p3.',
                52,
            ],
            [
                'Method call TYPO3\CMS\PHPStan\Tests\Rules\Classes\NamedArgumentUsageRule\Fixtures\NamedArgumentFixture::targetMethod() uses named arguments. It skips the following optional parameters: p3.',
                60,
            ],
        ]);
    }

    public function testValidCase(): void
    {
        $this->analyse([__DIR__ . '/Fixtures/ConstructorFixture.php'], []);
    }
}
