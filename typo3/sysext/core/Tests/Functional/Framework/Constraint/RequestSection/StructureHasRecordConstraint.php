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
class StructureHasRecordConstraint extends AbstractStructureRecordConstraint
{
    /**
     * @param ResponseSection $responseSection
     * @return bool
     */
    protected function matchesSection(ResponseSection $responseSection)
    {
        $nonMatchingVariants = [];
        $remainingRecordVariants = [];

        foreach ($responseSection->findStructures($this->recordIdentifier, $this->recordField) as $path => $structure) {
            if (empty($structure) || !is_array($structure)) {
                $this->sectionFailures[$responseSection->getIdentifier()] = 'No records found in "' . $path . '"';
                return false;
            }

            $remainingRecords = [];
            $nonMatchingValues = $this->getNonMatchingValues($structure);

            if ($this->strict) {
                $remainingRecords = $this->getRemainingRecords($structure);
            }

            if (empty($nonMatchingValues) && (!$this->strict || empty($remainingRecords))) {
                return true;
            }

            if (!empty($nonMatchingValues)) {
                $nonMatchingVariants[$path] = $nonMatchingValues;
            }
            if ($this->strict && !empty($remainingRecords)) {
                $remainingRecordVariants[$path] = $remainingRecords;
            }
        }

        $failureMessage = '';

        if (!empty($nonMatchingVariants)) {
            $failureMessage .= 'Could not assert all values for "' . $this->table . '.' . $this->field . '"' . LF;
            foreach ($nonMatchingVariants as $path => $nonMatchingValues) {
                $failureMessage .= '  * Not found in "' . $path . '": ' . implode(', ', $nonMatchingValues) . LF;
            }
        }

        if (!empty($remainingRecordVariants)) {
            $failureMessage .= 'Found remaining records for "' . $this->table . '.' . $this->field . '"' . LF;
            foreach ($remainingRecordVariants as $path => $remainingRecords) {
                $failureMessage .= '  * Found in "' . $path . '": ' . implode(', ', array_keys($remainingRecords)) . LF;
            }
        }

        $this->sectionFailures[$responseSection->getIdentifier()] = $failureMessage;
        return false;
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString()
    {
        return 'structure has record';
    }
}
