==============================================================
Breaking: #61782 - deprecated DocumentTemplate classes removed
==============================================================

Description
===========

The following deprecated classes are removed:

\TYPO3\CMS\Backend\Template\MediumDocumentTemplate
\TYPO3\CMS\Backend\Template\SmallDocumentTemplate
\TYPO3\CMS\Backend\Template\StandardDocumentTemplate


Impact
======

Extensions that still use one of the removed classes for their backend module won't work.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses one of the removed classes.


Migration
=========

Use \TYPO3\CMS\Backend\Template\DocumentTemplate instead of the removed class.