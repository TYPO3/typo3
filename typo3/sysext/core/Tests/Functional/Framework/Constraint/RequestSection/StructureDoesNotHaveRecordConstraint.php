<?php
namespace TYPO3\CMS\Core\Tests\Functional\Framework\Constraint\RequestSection;

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

use TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\ResponseSection;

/**
 * Model of frontend response
 */
class StructureDoesNotHaveRecordConstraint extends AbstractStructureRecordConstraint
{
    /**
     * @param ResponseSection $responseSection
     * @return bool
     */
    protected function matchesSection(ResponseSection $responseSection)
    {
        $matchingVariants = [];

        foreach ($responseSection->findStructures($this->recordIdentifier, $this->recordField) as $path => $structure) {
            if (empty($structure) || !is_array($structure)) {
                $this->sectionFailures[$responseSection->getIdentifier()] = 'No records found in "' . $path . '"';
                return false;
            }

            $nonMatchingValues = $this->getNonMatchingValues($structure);
            $matchingValues = array_diff($this->values, $nonMatchingValues);

            if (!empty($matchingValues)) {
                $matchingVariants[$path] = $matchingValues;
            }
        }

        if (empty($matchingVariants)) {
            return true;
        }

        $matchingMessage = '';
        foreach ($matchingVariants as $path => $matchingValues) {
            $matchingMessage .= '  * Found in "' . $path . '": ' . implode(', ', $matchingValues);
        }

        $this->sectionFailures[$responseSection->getIdentifier()] = 'Could not assert not having values for "' . $this->table . '.' . $this->field . '"' . LF . $matchingMessage;
        return false;
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString()
    {
        return 'structure does not have record';
    }
}
