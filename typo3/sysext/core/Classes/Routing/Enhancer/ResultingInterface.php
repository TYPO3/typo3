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

use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Routing\Route;

/**
 * Extend the Resulting Interface to explain that this route builds the page arguments itself, instead of having
 * the PageRouter having to deal with that.
 */
interface ResultingInterface
{
    /**
     * @param Route $route
     * @param array $results
     * @param array $remainingQueryParameters
     * @return PageArguments
     */
    public function buildResult(Route $route, array $results, array $remainingQueryParameters = []): PageArguments;
}
