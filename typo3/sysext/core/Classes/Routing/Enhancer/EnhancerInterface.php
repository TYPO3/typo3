<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Routing\Enhancer;

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

use TYPO3\CMS\Core\Routing\Aspect\AspectInterface;

/**
 * Base interface for enhancers, which can be decorators for adding parameters,
 * or routing enhancers which adds variants to a page.
 */
interface EnhancerInterface
{
    /**
     * @param AspectInterface[] $aspects
     */
    public function setAspects(array $aspects): void;

    /**
     * @return AspectInterface[]
     */
    public function getAspects(): array;
}
