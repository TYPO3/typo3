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

namespace TYPO3\CMS\Backend\ElementBrowser;

/**
 * Registry for element browsers. The registry receives all services, tagged with "recordlist.elementbrowser".
 * The tagging of element browsers is automatically done based on the implemented ElementBrowserInterface.
 *
 * @internal
 */
class ElementBrowserRegistry
{
    /**
     * @var ElementBrowserInterface[]
     */
    private array $elementBrowsers = [];

    public function __construct(iterable $elementBrowsers)
    {
        foreach ($elementBrowsers as $elementBrowser) {
            if (!($elementBrowser instanceof ElementBrowserInterface)) {
                continue;
            }

            $identifier = $elementBrowser->getIdentifier();
            if ($identifier === '') {
                throw new \InvalidArgumentException('Identifier for element browser ' . get_class($elementBrowser) . ' is empty.', 1647241084);
            }
            if (isset($this->elementBrowsers[$identifier])) {
                throw new \InvalidArgumentException('Element browser with identifier ' . $identifier . ' is already registered.', 1647241085);
            }
            $this->elementBrowsers[$identifier] = $elementBrowser;
        }
    }

    /**
     * Whether a registered element browser exists for the identifier
     */
    public function hasElementBrowser(string $identifier): bool
    {
        return isset($this->elementBrowsers[$identifier]);
    }

    /**
     * Get registered element browser by identifier
     */
    public function getElementBrowser(string $identifier): ElementBrowserInterface
    {
        if (!$this->hasElementBrowser($identifier)) {
            throw new \UnexpectedValueException('Element browser with identifier ' . $identifier . ' is not registered.', 1647241086);
        }

        return $this->elementBrowsers[$identifier];
    }

    /**
     * Get all registered element browsers
     *
     * @return ElementBrowserInterface[]
     */
    public function getElementBrowsers(): array
    {
        return $this->elementBrowsers;
    }
}
