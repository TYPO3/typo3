=========================================================
Breaking: #62859 - Removal of doc:link.action view helper
=========================================================

Description
===========

The view helper \TYPO3\CMS\Documentation\ViewHelpers\Link\Action is removed.


Impact
======

Extensions that rely on existence of \TYPO3\CMS\Documentation\ViewHelpers\Link\Action won't work.


Affected installations
======================

A TYPO3 instance is affected if a 3rd party extension uses the removed view helper.


Migration
=========

Either use f:be.buttons.icon or any of f:uri.* view helpers.