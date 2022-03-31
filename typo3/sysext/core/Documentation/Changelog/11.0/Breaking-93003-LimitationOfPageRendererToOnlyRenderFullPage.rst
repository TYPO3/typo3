.. include:: /Includes.rst.txt

======================================================
Breaking: #93003 - PageRenderer renders only full page
======================================================

See :issue:`93003`

Description
===========

TYPO3s main API class to build a full HTML page for Frontend
and Backend rendering - :php:`PageRenderer` - previously allowed
to only render the header or footer separately, which was built
due to historical reasons when rendering content.

This is however obsolete and TYPO3 Core only renders full pages in
Frontend and Backend internally.

For this reason, PageRenderer's :php:`render` method does not accept
any method arguments anymore and always renders the complete HTML page.

In addition, the constants

* :php:`PageRenderer::PART_COMPLETE`
* :php:`PageRenderer::PART_HEADER`
* :php:`PageRenderer::PART_FOOTER`

are now marked as protected and should not be accessed from outside
the PHP class anymore.


Impact
======

Calling :php:`PageRenderer->render()` does not respect any given
method argument.


Affected Installations
======================

TYPO3 installations with custom extensions manipulating the underlying
API to render the page.


Migration
=========

It is recommended for third-party extensions to use custom hooks to
process or manipulate header or footer parts.

.. index:: Backend, Frontend, PartiallyScanned, ext:core
