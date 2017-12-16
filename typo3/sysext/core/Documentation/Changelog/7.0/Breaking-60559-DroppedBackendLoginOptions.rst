
.. include:: ../../Includes.txt

================================================
Breaking: #60559 - Dropped Backend Login Options
================================================

See :issue:`60559`

Description
===========

Handling of :code:`$GLOBALS['TBE_STYLES']['loginBoxImage_rotationFolder']` and :code:`$GLOBALS['TBE_STYLES']['loginBoxImage_author']` was dropped.


Impact
======

Setting those options has no effect anymore.


Affected installations
======================

These options had no effect with standard core internal login screen based on t3skin for a long time already. Instances are
only affected if a 3rd party extension is loaded that changes the backend login screen and operates with these settings.


Migration
=========

Remove these options and their usage from the affected 3rd party extension or unload the extension.
