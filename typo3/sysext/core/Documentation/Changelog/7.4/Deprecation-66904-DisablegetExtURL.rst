
.. include:: ../../Includes.txt

====================================================================
Deprecation: #66904 - $disable Option in PageRepository->getExtURL()
====================================================================

See :issue:`66904`

Description
===========

The second parameter `$disable` within `PageRepository->getExtURL()` has been marked as deprecated.


Affected Installations
======================

Any installation using the method in a third-party extension above with using the second parameter set to
`true` will throw a deprecation warning.


Migration
=========

Check if redirects are enabled before the actual call to `PageRepository->getExtURL()` in a third-party extension.
