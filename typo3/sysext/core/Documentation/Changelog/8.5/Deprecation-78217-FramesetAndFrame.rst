.. include:: ../../Includes.txt

========================================
Deprecation: #78217 - frameset and frame
========================================

See :issue:`78217`

Description
===========

Frameset and frame are not supported in HTML5_ anymore.
The browser support for framesets could be dropped in the future.

Creating a layout based on framesets has been marked deprecated:

*  DocumentationFrame_
*  DocumentationFrameset_

The following TypoScript has been marked as deprecated:

*  :typoscript:`config.frameReloadIfNotInFrameset`
*  :typoscript:`config.doctype = xhtml_frames`
*  :typoscript:`config.xhtmlDoctype= xhtml_frames`
*  :typoscript:`frameSet` and its options
*  :typoscript:`FRAME` and its options
*  :typoscript:`FRAMESET` and its options

Furthermore the class :php:`FramesetRenderer` has been marked as deprecated.

.. _HTML5: https://www.w3.org/TR/html5/obsolete.html#frames
.. _DocumentationFrame: https://docs.typo3.org/typo3cms/TyposcriptReference/Setup/Frame/Index.html
.. _DocumentationFrameset: https://docs.typo3.org/typo3cms/TyposcriptReference/Setup/Frameset/Index.html


Impact
======

Using framesets will trigger deprecation log entries.


Affected Installations
======================

All installations using framesets.


Migration
=========

None.

.. index:: Frontend, TypoScript
