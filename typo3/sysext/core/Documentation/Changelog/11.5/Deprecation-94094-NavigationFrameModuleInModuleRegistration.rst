.. include:: /Includes.rst.txt

==================================================================
Deprecation: #94094 - navigationFrameModule in Module Registration
==================================================================

See :issue:`94094`

Description
===========

TYPO3 allowed for each module to include an iFrame for the navigation area with
the option :php:`navigationFrameModule` and :php:`navigationFrameModuleParameters`.
Since TYPO3 4.5 it was possible to also use a JavaScript component instead
via :php:`navigationComponentId`.

TYPO3 v11 allows to use Web Components for the :php:`navigationComponentId` option,
and all Core-based navigation components have been migrated to Lit-based
Web Components.

With this technology, TYPO3 does not need to handle iFrames for
the navigation area anymore, which is why the feature, together
with the option :php:`navigationFrameModule` has been marked as deprecated.


Impact
======

TYPO3 installations with third-party extensions registering
custom navigation iFrames will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

TYPO3 installations with third-party extensions shipping modules
with a custom navigation iFrame.


Migration
=========

Migration should be done by using Web Components, as this is much
faster and allows for better interoperability due to less usages of iFrames.

.. index:: Backend, NotScanned, ext:backend
