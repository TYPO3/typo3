=================================================================================
Breaking: #61828 - deprecated isDisplayCondition function from FormEngine removed
=================================================================================

Description
===========

Method :php:`isDisplayCondition()` from :php:`\TYPO3\CMS\Backend\Form\FormEngine` has been removed.


Impact
======

Extensions that still use the function :php:`isDisplayCondition()` will trigger a fatal
PHP error when records are edited in the backend.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses the removed function.


Migration
=========

Use :php:`\TYPO3\CMS\Backend\Form\ElementConditionMatcher` instead.



/** @var $elementConditionMatcher \TYPO3\CMS\Backend\Form\ElementConditionMatcher */
$elementConditionMatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Form\ElementConditionMatcher::class);
$elementConditionMatcher->match($displayCond, $row, $ffValueKey);
