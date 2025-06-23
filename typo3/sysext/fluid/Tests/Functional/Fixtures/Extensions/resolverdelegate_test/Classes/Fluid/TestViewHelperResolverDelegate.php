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

namespace TYPO3Tests\ResolverdelegateTest\Fluid;

use TYPO3Fluid\Fluid\Core\ViewHelper\ViewHelperResolverDelegateInterface;
use TYPO3Tests\ResolverdelegateTest\Service\TestService;

final readonly class TestViewHelperResolverDelegate implements ViewHelperResolverDelegateInterface
{
    public function __construct(
        private TestService $testService
    ) {}

    public function resolveViewHelperClassName(string $viewHelperName): string
    {
        return $this->testService->generateViewHelperClassName();
    }

    public function getNamespace(): string
    {
        return self::class;
    }
}
