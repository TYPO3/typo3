.. include:: ../../Includes.txt

====================================================================
Feature: #92984 - PSR-7 Request available in Frontend ContentObjects
====================================================================

See :issue:`92984`

Description
===========

The main Request object of a web-based PHP process is now handed into all
ContentObjects and ContentObjectRenderer classes.

In addition, any kind of "userFunc" methods initiated from ContentObjectRenderer,
basically all custom Frontend PHP code, now receive the request object that was
handed in as third method argument.

The ContentObjectRenderer API now has a :php: `getRequest()` method.

Example:

.. code-block:: typoscript

   page.10 = USER
   page.10.userFunc = MyVendor\MyPackage\Frontend\MyClass->myMethod

.. code-block:: php

   <?php

   namespace MyVendor\MyPackage\Frontend;

   class MyClass
   {

       public function myMethod(string $content, array $configuration, ServerRequestInterface $request)
       {
           $myValue = $request->getQueryParams()['myGetParameter'];
           $normalizedParams = $request->getAttribute('normalizedParams');
       }
   }

This functionality should be used in PHP code related to Frontend code instead of
the superglobal variables like `$_GET` / `$_POST` / `$_SERVER`, or TYPO3's
API methods `GeneralUtility::_GP()` and `GeneralUtility::getIndpEnv()`.

Impact
======

Any kind of custom Content Object in PHP code can now access the PSR-7 Request
object to fetch information about the current request, making TYPO3 Frontend
aware of PSR-7 standardized request objects.

.. index:: Frontend, PHP-API, ext:frontend
