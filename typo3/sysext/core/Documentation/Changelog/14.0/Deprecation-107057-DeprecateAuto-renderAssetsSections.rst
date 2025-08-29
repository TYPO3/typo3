..  include:: /Includes.rst.txt

..  _deprecation-107057-1756471326:

============================================================
Deprecation: #107057 - Deprecate auto-render assets sections
============================================================

See :issue:`107057`

Description
===========

The auto-rendering of template sections :php:`HeaderAssets` and
:php:`FooterAssets` available in Fluid templates has been marked as deprecated
in TYPO3 v14.0 and will be removed in TYPO3 v15.0.


Impact
======

Using the deprecated sections  will raise a deprecation level log error and
will stop working in TYPO3 v15.0.


Affected installations
======================

TYPO3 installations using the sections :php:`HeaderAssets` and
:php:`FooterAssets` in Fluid templates.


Migration
=========

It is recommended to use the :php:`f:asset.script` or :php:`f:asset.css`
ViewHelpers from the TYPO3 Asset Collector API to render required assets.

In scenarios, where the :php:`f:asset.script` or :php:`f:asset.css` ViewHelpers
are not suitable, users can use the :php:`f:page.headerData` or
:php:`f:page.footerData` ViewHelpers to render custom HTML header or footer
markup.

..  index:: Frontend, NotScanned, ext:frontend
