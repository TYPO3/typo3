==========================================================
Deprecation: #68128 - GeneralUtility slash-related methods
==========================================================

Description
===========

The following methods within GeneralUtility used to add or remove slashes have been marked as deprecated.

.. code-block:: php

	GeneralUtility::addSlashesOnArray()
	GeneralUtility::stripSlashesOnArray()
	GeneralUtility::slashArray()


Impact
======

Any usage of these methods will throw a deprecation warning.


Affected Installations
======================

Extensions that call these PHP methods directly.
