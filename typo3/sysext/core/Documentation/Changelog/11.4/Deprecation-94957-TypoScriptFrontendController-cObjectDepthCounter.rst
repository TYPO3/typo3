.. include:: /Includes.rst.txt

=======================================================================
Deprecation: #94957 - TypoScriptFrontendController->cObjectDepthCounter
=======================================================================

See :issue:`94957`

Description
===========

The :php:`TypoScriptFrontendController` contains a property to prevent endless
recursion of content objects during frontend rendering. With TypoScript
becoming less complex, this check becomes obsolete. To reduce dependencies
between :php:`TypoScriptFrontendController` and :php:`ContentObjectRenderer`,
the handling has been removed and property :php:`TypoScriptFrontendController->cObjectDepthCounter`
has been marked as deprecated.


Impact
======

If a TypoScript setup somehow manages to create a recursion, PHP will now stop
with a fatal PHP nesting level error at some point, instead TYPO3 frontend
rendering silently stopping.


Affected Installations
======================

Instances using property :php:`TypoScriptFrontendController->cObjectDepthCounter`
are affected. That property has been handled mostly internally, this case is unlikely.
The extension scanner will find usages with a weak match.


Migration
=========

Drop usages of property :php:`TypoScriptFrontendController->cObjectDepthCounter`,
it is unused within TYPO3 v11.

.. index:: Frontend, PHP-API, FullyScanned, ext:frontend
