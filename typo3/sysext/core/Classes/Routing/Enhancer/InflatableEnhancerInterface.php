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

/**
 * Interface asserting that enhancer is capable of inflating parameters.
 */
interface InflatableEnhancerInterface
{
    public function inflateParameters(array $parameters, array $internals = []): array;
}
