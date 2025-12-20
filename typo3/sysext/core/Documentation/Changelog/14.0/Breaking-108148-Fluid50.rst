..  include:: /Includes.rst.txt

..  _breaking-108148-1766234207:

=============================
Breaking: #108148 - Fluid 5.0
=============================

See :issue:`108148`

Description
===========

Fluid 5.0 removes pre-announced deprecations that were introduced with Fluid 2.x
and 4.x.

Impact
======

Installations that use methods that were deprecated with Fluid 2.x or Fluid 4.x
will now encounter PHP errors.

Affected installations
======================

Breaking changes are listed in the Fluid documentation:

`Changelog 5.x <https://docs.typo3.org/permalink/fluid:changelog-5-x>`_

Migration
=========

Noteworthy deprecations and mitigations have already been communicated with
TYPO3 changelog entries in 13.x:

* `Deprecation: #104223 - Fluid standalone methods <https://docs.typo3.org/permalink/changelog:deprecation-104223-1721383576>`_
* `Deprecation: #104463 - Fluid standalone overrideArgument <https://docs.typo3.org/permalink/changelog:deprecation-104463-1721754926>`_
* `Deprecation: #104789 - renderStatic() for Fluid ViewHelpers <https://docs.typo3.org/permalink/changelog:deprecation-104789-1725195584>`_

Deprecation items in Fluid changelogs might contain additional hints:

* `Fluid Changelog 2.x <https://docs.typo3.org/permalink/fluid:changelog-2-x>`_
* `Fluid Changelog 4.x <https://docs.typo3.org/permalink/fluid:changelog-4-x>`_

..  index:: Fluid, PartiallyScanned, ext:fluid
