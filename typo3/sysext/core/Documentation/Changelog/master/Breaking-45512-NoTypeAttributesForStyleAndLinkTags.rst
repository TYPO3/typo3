.. include:: ../../Includes.txt

=============================================================
Breaking: #45512 - No type attributes for style and link tags
=============================================================

See :issue:`45512`

Description
===========

It is recommended for `<style>` and `<link>` HTML tags
to not use the "type" attribute anymore.

These references state its recommended practice to omit them:

- https://developer.mozilla.org/en-US/docs/Web/HTML/Element/link
- https://developer.mozilla.org/en-US/docs/Web/HTML/Element/style

For this reason, TYPO3 does not add this "type" attribute to the mentioned
HTML elements anymore when rendering HTML.

Impact
======

The attribute `type` is removed from the HTML tags `<style>` and `<link>`
by default for TYPO3 Backend and Frontend output.

Affected Installations
======================

All installations of TYPO3 that use `<style>` or `<link>` tags are affected.
The probability this has negative impact on the user experience is low, however.


Migration
=========

If requested due to very old browser requirements for TYPO3 Frontend,
the type attribute can be added via TypoScript options or Fluid
AssetCollector attributes again.

.. index:: Backend, Frontend, NotScanned, ext:core
