
.. include:: /Includes.rst.txt

==========================================================
Deprecation: #68128 - GeneralUtility slash-related methods
==========================================================

See :issue:`68128`

Description
===========

The following methods within `GeneralUtility` used to add or remove slashes
have been marked as deprecated.

.. code-block:: php

	GeneralUtility::addSlashesOnArray()
	GeneralUtility::stripSlashesOnArray()
	GeneralUtility::slashArray()


Impact
======

Any usage of these methods will trigger a deprecation log entry.


Affected Installations
======================

Extensions that call these PHP methods directly.

Migration
=========

Remove usage of these methods from custom extensions.


.. index:: PHP-API
