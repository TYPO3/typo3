.. include:: /Includes.rst.txt

.. _deprecation-100405-1680520177:

==================================================================
Deprecation: #100405 - Property TypoScriptFrontendController->type
==================================================================

See :issue:`100405`

Description
===========

The public property :php:`type` of the main class in TYPO3 Frontend
:php:`TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController` has been
marked as internal, as it should not be used outside of this PHP class anymore
in the future.

This is part of the overall part to reduce dependencies on this PHP class, as
it isn't always available in TYPO3 Frontend.


Impact
======

Accessing this property will trigger a PHP deprecation notice. Accessing this
property might also happen via TypoScript and TypoScript conditions.


Affected installations
======================

TYPO3 installations using this property on checking various typeNum settings
from TypoScript.


Migration
=========

When using this property in PHP code via :php:`$GLOBALS['TSFE']->type`, it is
recommended to move to the PSR-7 request via
:php:`$request->getAttribute('routing')->getPageType()`, which is the property
of the :php:`PageArguments` object, as a result of the :php:`GET` parameter
:php:`type`, or `$GLOBALS['TSFE']->getPageArguments()->getPageType()` if
the request object is not available.

Within TypoScript, conditions and getData properties need to be adapted:

Before: :typoscript:`[getTSFE() && getTSFE().type == 13]`
After: :typoscript:`[request.getPageArguments().getPageType() == 13]`

In Typoscript getData attributes:

Before: :typoscript:`page.10.data = TSFE:type`
After: :typoscript:`page.10.data = request:routing|pageType`


.. index:: Frontend, TypoScript, FullyScanned, ext:frontend
