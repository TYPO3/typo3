.. include:: /Includes.rst.txt

==================================================================
Deprecation: #92992 - Hook t3lib_parsehtml_proc.php:transformation
==================================================================

See :issue:`92992`

Description
===========

Since the deprecation of several internal functions in the
:php:`TYPO3\CMS\Core\Html\RteHtmlParser` in TYPO3 10.2 (`Changelog entry #86440
<https://docs.typo3.org/c/typo3/cms-core/10.2/en-us/Changelog/9.5/Deprecation-86440-InternalMethodsAndPropertiesWithinRteHtmlParser.html>`_)
the hook :php:`t3lib/class.t3lib_parsehtml_proc.php:transformation` became quite useless.

It is therefore marked as deprecated and will be removed with TYPO3 v12.

Impact
======

Calling the hook will trigger a PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations with extensions installed that implement the hook.


Migration
=========

Migrate to use the public API only and use other options (such as
:php:`allowAttributes`) in order to only run certain instructions on the :php:`RteHtmlParser` object.

.. index:: RTE, NotScanned, ext:core
