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

Using the :php:`UriBuilder` class of Extbase without a local
request object will trigger a PHP deprecation warning.

Additionally, when using the :php:`UriBuilder` to build frontend URLs, the
current content object is required. It is initialized from the handed in local
request object. This means, in case extensions do set the request object,
a automatic fallback is applied in v12, triggering a PHP deprecation warning, as
it will be removed in v13, too.


Affected installations
======================

TYPO3 installations with custom extensions initializing the :php:`UriBuilder`
without handing in a request object and using it to build URIs.


Migration
=========

Make sure to call :php:`setRequest($request)` before using the
:php:`UriBuilder`, when no other component has done this already.

Using ViewHelpers will not trigger the warning, as the TYPO3 Core ensures
the proper setup.

.. index:: PHP-API, FullyScanned, ext:core
