=======================================================
Breaking: #67076 - core/Build folder moved to top level
=======================================================

Description
===========

The content of the former build folder ``typo3/sysext/core/Build``  has been moved to the top level folder ``Build``.


Impact
======

All test environments, scripts and manuals need to be updated to use the new (shorter) paths.


Affected Installations
======================

Any extension or test environment using the old paths will stop working.


Migration
=========

Update your scripts, shortcuts, alias, etc to the new (shorter) path.
