
.. include:: /Includes.rst.txt

========================================================================
Breaking: #64643 - Remove functionality for enable_typo3temp_db_tracking
========================================================================

See :issue:`64643`

Description
===========

The logic and the database table for tracking generated typo3temp/ images by GraphicalFunctions have been removed
without substitution. The option to enable this functionality `$TYPO3_CONF_VARS[GFX][enable_typo3temp_db_tracking]`
has been removed.


Impact
======

Images generated with GraphicalFunctions with the option above enabled will not be tracked in the database anymore.


Affected installations
======================

Any installation having the option `$TYPO3_CONF_VARS[GFX][enable_typo3temp_db_tracking]` enabled.


Migration
=========

If the functionality or a similar functionality is needed for some edge-cases, an own implementation via a third-party
extension is necessary.


.. index:: LocalConfiguration
