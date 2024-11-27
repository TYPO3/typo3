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

namespace TYPO3\CMS\Core\Database;

use TYPO3\CMS\Core\Service\DependencyOrderingService;

/**
 * @internal
 */
readonly class DriverMiddlewareService
{
    public function __construct(
        private DependencyOrderingService $dependencyOrderingService
    ) {}

    public function order(array $middlewares): array
    {
        return $this->dependencyOrderingService->orderByDependencies($middlewares);
    }

    /**
     * @param array $middleware
     * @return array{target: class-string, disabled: bool, after: string[], before: string[], type: string}
     */
    public function ensureCompleteMiddlewareConfiguration(array $middleware): array
    {
        $target = (string)($middleware['target'] ?? '');
        if ($target === '' || !class_exists($target)) {
            throw new \RuntimeException(
                'Doctrine DBAL driver middleware registration requires a valid class-name as "target".',
                1701546655
            );
        }
        return [
            'target' => $target,
            'disabled' => (bool)($middleware['disabled'] ?? false),
            'after' => (array)($middleware['after'] ?? []),
            'before' => (array)($middleware['before'] ?? []),
            'type' => '',
        ];
    }
}
