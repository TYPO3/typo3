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

namespace TYPO3\CMS\Core\Authentication\Pipeline;

use TYPO3\CMS\Core\Authentication\Principal;

/**
 * Successful authentication outcome carrying the established Principal.
 * Attributes transport further facts about the authentication, like the
 * attributes of a Symfony security token, for example the legacy user
 * object during the migration.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication pipeline groundwork
 */
final readonly class Authenticated implements AuthenticationResult
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public Principal $principal,
        public array $attributes = [],
    ) {}

    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    public function getAttribute(string $name): mixed
    {
        if (!$this->hasAttribute($name)) {
            throw new \InvalidArgumentException('The authentication outcome has no attribute "' . $name . '".', 1792934547);
        }
        return $this->attributes[$name];
    }
}
