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

namespace TYPO3\CMS\Frontend\Typolink;

/**
 * @internal Might be removed in TYPO3 v15, when LinkResultInterface is consolidated
 */
final readonly class LinkResultService
{
    public function fromState(array $state): LinkResultInterface&LinkResultStateInterface
    {
        $className = $state['className'] ?? null;
        if (!is_string($className)
            || !is_a($className, LinkResultInterface::class, true)
            || !is_a($className, LinkResultStateInterface::class, true)
        ) {
            throw new \InvalidArgumentException('Invalid state provided', 1769678520);
        }
        /** @var LinkResultInterface&LinkResultStateInterface */
        return $className::fromState($state);
    }

    /**
     * Extract state from a LinkResultInterface for serialization.
     *
     * @throws \InvalidArgumentException if $linkResult does not implement LinkResultStateInterface
     */
    public function getState(LinkResultInterface $linkResult): array
    {
        if ($linkResult instanceof LinkResultStateInterface) {
            return $linkResult->getState();
        }
        throw new \InvalidArgumentException(
            sprintf(
                'LinkResult of type %s does not implement %s and cannot be serialized',
                get_class($linkResult),
                LinkResultStateInterface::class
            ),
            1769791714
        );
    }
}
