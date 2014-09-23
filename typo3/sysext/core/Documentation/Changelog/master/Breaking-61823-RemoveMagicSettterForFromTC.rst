==================================================
Breaking: #61823 - Remove magic setter for $fromTC
==================================================

Description
===========

The magic setter for $fromTC in \TYPO3\CMS\Core\Database\RelationHandler is removed.


Impact
======

Directly setting the now protected property $fromTC will trigger a PHP warning.


Affected installations
======================

Any installation using an extension that sets $fromTC property directly.


Migration
=========

Use \TYPO3\CMS\Core\Database\RelationHandler::setFetchAllFields() instead.

