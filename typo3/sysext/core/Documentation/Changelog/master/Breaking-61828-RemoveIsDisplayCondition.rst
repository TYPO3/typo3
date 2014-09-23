=================================================================================
Breaking: #61828 - deprecated isDisplayCondition function from FormEngine removed
=================================================================================

Description
===========

Method isDisplayCondition() from \TYPO3\CMS\Backend\Form\FormEngine is removed.


Impact
======

Extensions that still use the function isDisplayCondition will trigger a fatal
PHP error when records are edited in the backend.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses the removed function.


Migration
=========

Use \TYPO3\CMS\Backend\Form\ElementConditionMatcher instead of \TYPO3\CMS\Backend\Form\FormEngine::isDisplayCondition

/** @var $elementConditionMatcher \TYPO3\CMS\Backend\Form\ElementConditionMatcher */
$elementConditionMatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\ElementConditionMatcher');
$elementConditionMatcher->match($displayCond, $row, $ffValueKey);
