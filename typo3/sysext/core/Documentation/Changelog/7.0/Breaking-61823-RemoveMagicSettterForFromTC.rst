
.. include:: /Includes.rst.txt

==================================================
Breaking: #61823 - Remove magic setter for $fromTC
==================================================

See :issue:`61823`

Description
===========

The magic setter for :code:`$fromTC` in \TYPO3\CMS\Core\Database\RelationHandler is removed.


Impact
======

Directly setting the protected property :code:`$fromTC` will trigger a PHP warning.


Affected installations
======================

Any installation using an extension that sets :code:`$fromTC` property directly.


Migration
=========

Use :code:`\TYPO3\CMS\Core\Database\RelationHandler::setFetchAllFields()` instead.


.. index:: PHP-API, Backend
