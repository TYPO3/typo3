==================================================
Breaking: #61823 - Remove magic setter for $fromTC
==================================================

Description
===========

The magic setter for :php:`$fromTC` in \TYPO3\CMS\Core\Database\RelationHandler is removed.


Impact
======

Directly setting the protected property :php:`$fromTC` will trigger a PHP warning.


Affected installations
======================

Any installation using an extension that sets :php:`$fromTC` property directly.


Migration
=========

Use :php:`\TYPO3\CMS\Core\Database\RelationHandler::setFetchAllFields()` instead.

