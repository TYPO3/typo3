===================================================================
Breaking: #61786 - remove deprecated TypeHandlingService in extbase
===================================================================

Description
===========

The TypeHandlingService class is removed from the extbase extension.


Impact
======

Extensions that still use :php:`\TYPO3\CMS\Extbase\Service\TypeHandlingService` won't work.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses the removed class :php:`\TYPO3\CMS\Extbase\Service\TypeHandlingService`.


Migration
=========

Replace all calls to :php:`\TYPO3\CMS\Extbase\Service\TypeHandlingService` functions to their new static functions in :php:`\TYPO3\CMS\Extbase\Utility\TypeHandlingUtility`