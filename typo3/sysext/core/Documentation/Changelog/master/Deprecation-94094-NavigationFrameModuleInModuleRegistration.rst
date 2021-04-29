.. include:: ../../Includes.txt

==================================================================
Deprecation: #94094 - navigationFrameModule in Module Registration
==================================================================

See :issue:`94094`

Description
===========

TYPO3 allowed for each module to include an iframe for the navigation area with the option `navigationFrameModule` and `navigationFrameModuleParameters`. Since TYPO3 4.5 it was possible to also use a JavaScript component instead via `navigationComponentId`.

TYPO3 v11 allows to use Web Components for the `navigationComponentId` option, and all core-based navigation components have been migrated to Lit-based Web Components.

With this technology, TYPO3 does not need to handle iframes for
the navigation area anymore, which is why the feature, together
with the option `navigationFrameModule` has been marked as deprecated.


Impact
======

TYPO3 installations with third-party extensions registering
custom navigation iframes will trigger a deprecation log entry.


Affected Installations
======================

TYPO3 installations with third-party extensions shipping modules
with a custom navigation iframe.


Migration
=========

Migration should be done to use Web Components, as this is much
faster and allows for better operability due to less usages of iframes.

.. index:: Backend, NotScanned, ext:backend