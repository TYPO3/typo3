
.. include:: ../../Includes.txt

===================================================
Breaking: #63000 - Migrate EXT:cshmanual to Extbase
===================================================

See :issue:`63000`

Description
===========

The extension "cshmanual" has been migrated to a newer code base by using Extbase and Fluid.


Impact
======

Any call to the previous public methods of the old controller HelpModuleController will fail as the code base changed.


Affected installations
======================

Any installation using an extension which calls the previously available methods directly.


Migration
=========

Use the Extbase controller or Repository class.
