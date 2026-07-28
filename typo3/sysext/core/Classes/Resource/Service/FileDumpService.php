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

namespace TYPO3\CMS\Core\Resource\Service;

use TYPO3\CMS\Core\Crypto\HashService;

/**
 * @internal
 */
final readonly class FileDumpService
{
    private const ADDITIONAL_SECRET = 'resourceStorageDumpFile';

    public function __construct(
        private HashService $hashService,
    ) {}

    /**
     * @param array<string, int|string> $parameters
     * @param string $token
     */
    public function isTokenValid(array $parameters, string $token): bool
    {
        if ($token === '' || !$this->isSelectorConsistent($parameters)) {
            return false;
        }

        return hash_equals($this->createToken($parameters), $token);
    }

    /**
     * Creates the token based on the incoming parameters.
     * Note: This method is currently private on purpose, it may become public at a later point.
     *
     * @param array<string, int|string> $parameters
     */
    private function createToken(array $parameters): string
    {
        return $this->hashService->hmac(implode('|', $parameters), self::ADDITIONAL_SECRET);
    }

    /**
     * Ensures exactly one of the object selectors "f" (sys_file), "r" (sys_file_reference)
     * or "p" (sys_file_processedfile) is present, and that it matches the signed "t" value.
     *
     * @param array<string, int|string> $parameters
     */
    private function isSelectorConsistent(array $parameters): bool
    {
        $present = array_values(array_intersect(['f', 'r', 'p'], array_keys($parameters)));
        if (count($present) !== 1) {
            return false;
        }
        return ($parameters['t'] ?? null) === $present[0];
    }
}
