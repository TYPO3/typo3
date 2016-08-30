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
abstract class AbstractRecordConstraint extends \PHPUnit_Framework_Constraint
{
    /**
     * @var array
     */
    protected $sectionFailures = [];

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $field;

    /**
     * @var bool
     */
    protected $strict = false;

    /**
     * @var array
     */
    protected $values;

    public function setTable($table)
    {
        $this->table = $table;
        return $this;
    }

    public function setField($field)
    {
        $this->field = $field;
        return $this;
    }

    public function setValues()
    {
        $values = func_get_args();
        $this->values = $values;
        return $this;
    }

    public function setStrict($strict)
    {
        $this->strict = (bool)$strict;
        return $this;
    }

    /**
     * Evaluates the constraint for parameter $other. Returns true if the
     * constraint is met, false otherwise.
     *
     * @param array|ResponseSection|ResponseSection[] $other ResponseSections to evaluate
     * @return bool
     */
    protected function matches($other)
    {
        if (is_array($other)) {
            $success = null;
            foreach ($other as $item) {
                $currentSuccess = $this->matchesSection($item);
                $success = ($success === null ? $currentSuccess : $success || $currentSuccess);
            }
            return !empty($success);
        } else {
            return $this->matchesSection($other);
        }
    }

    /**
     * @param ResponseSection $responseSection
     * @return bool
     */
    abstract protected function matchesSection(ResponseSection $responseSection);

    /**
     * @param array $records
     * @return array
     */
    protected function getNonMatchingValues(array $records)
    {
        $values = $this->values;

        foreach ($records as $recordIdentifier => $recordData) {
            if (strpos($recordIdentifier, $this->table . ':') !== 0) {
                continue;
            }

            if (($foundValueIndex = array_search($recordData[$this->field], $values)) !== false) {
                unset($values[$foundValueIndex]);
            }
        }

        return $values;
    }

    /**
     * @param array $records
     * @return array
     */
    protected function getRemainingRecords(array $records)
    {
        $values = $this->values;

        foreach ($records as $recordIdentifier => $recordData) {
            if (strpos($recordIdentifier, $this->table . ':') !== 0) {
                unset($records[$recordIdentifier]);
                continue;
            }

            if (($foundValueIndex = array_search($recordData[$this->field], $values)) !== false) {
                unset($values[$foundValueIndex]);
                unset($records[$recordIdentifier]);
            }
        }

        return $records;
    }

    /**
     * Returns the description of the failure
     *
     * The beginning of failure messages is "Failed asserting that" in most
     * cases. This method should return the second part of that sentence.
     *
     * @param mixed $other Evaluated value or object.
     * @return string
     */
    protected function failureDescription($other)
    {
        return $this->toString();
    }

    /**
     * Return additional failure description where needed
     *
     * The function can be overridden to provide additional failure
     * information like a diff
     *
     * @param mixed $other Evaluated value or object.
     * @return string
     */
    protected function additionalFailureDescription($other)
    {
        $failureDescription = '';
        foreach ($this->sectionFailures as $sectionIdentifier => $sectionFailure) {
            $failureDescription .= '* Section "' . $sectionIdentifier . '": ' . $sectionFailure . LF;
        }
        return $failureDescription;
    }
}
