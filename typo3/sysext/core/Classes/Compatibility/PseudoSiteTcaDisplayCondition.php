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

namespace TYPO3\CMS\Core\Compatibility;

/**
 * A display condition that returns true if the page we are dealing
 * with is in a page tree that is represented by a PseudoSite object.
 *
 * This was used in TYPO3 v9 to suppress the 'slug' field in pseudo site page trees
 * when editing page records and to show the alias field.
 *
 * Since Pseudo Site Handling was removed, this class is obsolete, and there for
 * legacy reasons, but kept for TYPO3 v10
 *
 * @internal Implementation and class will vanish in TYPO3 v11 without further notice
 */
class PseudoSiteTcaDisplayCondition
{
    /**
     * @param array $parameters
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isInPseudoSite(array $parameters): bool
    {
        if ($parameters['conditionParameters'][1] === 'false') {
            // Negate if requested
            return true;
        }
        return false;
    }
}
