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

namespace TYPO3\CMS\Backend\Module;

/**
 * Registry for module access gates, ordered by before/after dependencies
 * during container compilation.
 *
 * @deprecated since v15, will be removed in v16. Decide module access with a security voter, see #[AsSecurityVoter].
 */
class ModuleAccessGateRegistry
{
    /**
     * @var array<string, ModuleAccessGateInterface>
     */
    private array $gates = [];

    /**
     * @param array<string, ModuleAccessGateInterface> $gates Pre-ordered gates (injected by ModuleAccessGatePass)
     */
    public function __construct(array $gates = [])
    {
        foreach ($gates as $identifier => $gate) {
            $this->gates[$identifier] = $gate;
        }
    }

    public function has(string $identifier): bool
    {
        return isset($this->gates[$identifier]);
    }

    /**
     * @throws \InvalidArgumentException if gate does not exist
     */
    public function get(string $identifier): ModuleAccessGateInterface
    {
        if (!$this->has($identifier)) {
            throw new \InvalidArgumentException(
                sprintf('Module access gate with identifier "%s" is not registered.', $identifier),
                1774436666
            );
        }
        return $this->gates[$identifier];
    }

    /**
     * @return array<string, ModuleAccessGateInterface>
     */
    public function getAll(): array
    {
        return $this->gates;
    }
}
