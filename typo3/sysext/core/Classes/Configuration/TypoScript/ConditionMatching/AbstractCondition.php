<?php
namespace TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching;

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
 * Abstract class to define own custom TypoScript conditions.
 *
 * Used with the TypoScript parser.
 */
abstract class AbstractCondition
{
    /**
     * @var \TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher
     */
    protected $conditionMatcherInstance;

    /**
     * @return \TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher
     */
    protected function getConditionMatcherInstance()
    {
        return $this->conditionMatcherInstance;
    }

    /**
     * @param \TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher $conditionMatcherInstance
     */
    public function setConditionMatcherInstance($conditionMatcherInstance)
    {
        $this->conditionMatcherInstance = $conditionMatcherInstance;
    }

    /**
     * Main method handling the evaluation.
     * Any given parameters given within the condition in form of
     * [ACME\MyPackageName\MyCondition = value1, = value2]
     * will be given as parameter in form of a numeric array, each entry
     * containing the strings that are split by the commas
     * e.g. array('= value1', '= value2')
     *
     * @param array $conditionParameters
     * @return bool
     */
    abstract public function matchCondition(array $conditionParameters);
}
