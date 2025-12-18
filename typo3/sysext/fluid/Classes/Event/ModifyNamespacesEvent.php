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

namespace TYPO3\CMS\Fluid\Event;

/**
 * Event to modify registered global Fluid namespaces before they
 * are passed to Fluid's ViewHelperResolver.
 */
final class ModifyNamespacesEvent
{
    /**
     * @param array<string, string[]> $namespaces
     */
    public function __construct(private array $namespaces) {}

    /**
     * @return array<string, string[]>
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * @param array<string, string[]> $namespaces
     */
    public function setNamespaces(array $namespaces): void
    {
        $this->namespaces = $namespaces;
    }
}
