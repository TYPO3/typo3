..  include:: /Includes.rst.txt

..  _breaking-109841-1778962451:

======================================================
Breaking: #109841 - Remove auto-render assets sections
======================================================

See :issue:`109841`

Description
===========

The auto-rendering Fluid template sections :php:`HeaderAssets` and :php:`FooterAssets` are no longer evaluated.


Impact
======

:php:`HeaderAssets` and :php:`FooterAssets` sections are ignored.


Affected installations
======================

TYPO3 installations using the sections :php:`HeaderAssets` and :php:`FooterAssets` in Fluid templates.


Migration
=========

See `Deprecation: #107057 - Deprecate auto-render assets sections <https://docs.typo3.org/permalink/changelog:deprecation-107057-1756471326>`_.

..  index:: Frontend, NotScanned, ext:frontend
