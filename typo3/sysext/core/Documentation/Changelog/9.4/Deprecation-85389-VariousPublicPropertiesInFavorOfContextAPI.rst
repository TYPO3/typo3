.. include:: ../../Includes.txt

=======================================================================
Deprecation: #85389 - Various public properties in favor of Context API
=======================================================================

See :issue:`85389`

Description
===========

The following properties have been marked as deprecated in favor of the newly introduced Context API:

* :php:`TypoScriptFrontendController->loginUser`
* :php:`TypoScriptFrontendController->gr_list`
* :php:`TypoScriptFrontendController->beUserLogin`
* :php:`TypoScriptFrontendController->showHiddenPage`
* :php:`TypoScriptFrontendController->showHiddenRecords`

The Context API supersedes the public properties in favor of decoupling the information from global objects.


Impact
======

Reading or writing information on any of the public properties will trigger a PHP :php:`E_USER_DEPRECATED` error,
however the value is still stored and contains the same information as before.


Affected Installations
======================

Any TYPO3 installation using extensions accessing this kind of information.


Migration
=========

Use Context API / Aspects instead to read from this information:

.. code-block:: php

   $context = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Context\Context::class);
   $context->getPropertyFromAspect('visibility', 'includeHiddenPages')` instead of :php:`$TSFE->showHiddenPage
   $context->getPropertyFromAspect('visibility', 'includeHiddenContent')` instead of :php:`$TSFE->showHiddenRecords
   $context->getPropertyFromAspect('frontend.user', 'isLoggedIn')` instead of :php:`$TSFE->loginUser
   $context->getPropertyFromAspect('backend.user', 'isLoggedIn')` instead of :php:`$TSFE->beUserLogin
   $context->getPropertyFromAspect('frontend.user', 'groupIds')` instead of :php:`$TSFE->gr_list

For more information see :ref:`Context API chapter<t3coreapi:context-api>` in TYPO3 Explained.

.. index:: Frontend, PHP-API, FullyScanned
