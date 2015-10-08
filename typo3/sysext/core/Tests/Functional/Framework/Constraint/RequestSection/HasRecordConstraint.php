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
class HasRecordConstraint extends AbstractRecordConstraint
{
    /**
     * @param ResponseSection $responseSection
     * @return bool
     */
    protected function matchesSection(ResponseSection $responseSection)
    {
        $records = $responseSection->getRecords();

        if (empty($records) || !is_array($records)) {
            $this->sectionFailures[$responseSection->getIdentifier()] = 'No records found.';
            return false;
        }

        $nonMatchingValues = $this->getNonMatchingValues($records);

        if (!empty($nonMatchingValues)) {
            $this->sectionFailures[$responseSection->getIdentifier()] = 'Could not assert all values for "' . $this->table . '.' . $this->field . '": ' . implode(', ', $nonMatchingValues);
            return false;
        }

        return true;
    }

    /**
     * Returns a string representation of the constraint.
     *
     * @return string
     */
    public function toString()
    {
        return 'response has records';
    }
}
