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

namespace TYPO3\CMS\Linkvalidator\Linktype;

/**
 * Registry for linktypes. The registry receives all services, tagged with "linkvalidator.linktype".
 * The tagging of linktype is automatically done based on the implemented LinktypeInterface.
 *
 * @internal
 */
class LinktypeRegistry
{
    private array $linktypes = [];

    public function __construct(iterable $linktypes)
    {
        foreach ($linktypes as $linktype) {
            if (!($linktype instanceof LinktypeInterface)) {
                continue;
            }

            $identifier = $linktype->getIdentifier();
            if ($identifier === '') {
                throw new \InvalidArgumentException('Identifier for linktype ' . get_class($linktype) . ' is empty.', 1644932383);
            }
            if (isset($this->linktypes[$identifier])) {
                throw new \InvalidArgumentException('Linktype identifier ' . $identifier . ' is already registered.', 1644932384);
            }

            $this->linktypes[$identifier] = $linktype;
        }
    }

    public function getLinktype(string $identifier): ?LinktypeInterface
    {
        return $this->linktypes[$identifier] ?? null;
    }

    /**
     * Get all registered linktypes
     *
     * @return LinktypeInterface[]
     */
    public function getLinktypes(): array
    {
        return $this->linktypes;
    }

    /**
     * Get the identifiers of all registered linktypes
     *
     * @return string[]
     */
    public function getIdentifiers(): array
    {
        return array_keys($this->linktypes);
    }
}
