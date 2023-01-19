.. include:: /Includes.rst.txt

.. _deprecation-99650-1674205203:

=======================================================================
Deprecation: #99650 - Global Request object usage in Extbase UriBuilder
=======================================================================

See :issue:`99650`

Description
===========

Usage of the global request object (:php:`$GLOBALS['TYPO3_REQUEST']`) as
fallback in the EXT:extbase :php:`\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder`
has been deprecated and will be removed in TYPO3 v13. The :php:`UriBuilder` will
then solely rely on a locally set request object.

Impact
======

Using the :php:`UriBuilder` class of extbase without a local
request object will trigger a PHP deprecation warning.


Affected installations
======================

TYPO3 installations with custom extensions initializing the :php:`UriBuilder`
without handing in a request object and using it to build URIs.


Migration
=========

Make sure to call :php:`setRequest($request)` before using the
:php:`UriBuilder`, when no other component has done this already.

Using ViewHelpers won't trigger the warning, as TYPO3 Core will take care by
itself to ensure the proper setup.

.. index:: PHP-API, FullyScanned, ext:core
