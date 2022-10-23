.. include:: /Includes.rst.txt

==============================================================================================
Feature: #91499 - Additional attributes for includeJS, includeCSS and all other page.include**
==============================================================================================

See :issue:`91499`

Description
===========

PageRenderer supports additional tag attributes for CSS and JavaScript files.
These data attributes can be configured using a key-value list via TypoScript.

* :typoscript:`page.includeCSS`
* :typoscript:`page.includeCSSLibs`
* :typoscript:`page.includeJS`
* :typoscript:`page.includeJSFooter`
* :typoscript:`page.includeJSLibs`
* :typoscript:`page.includeJSFooterlibs`


Impact
======

It is now possible to extend script and link tags with any kind of
HTML tag attributes, which is very useful for integration with
external scripts such as Consent manager.

Example
^^^^^^^

Configuration:

.. code-block:: typoscript

   page = PAGE
   page {
     includeCSSLibs {
       someIncludeFile = fileadmin/someIncludeFile1
       someIncludeFile.data-foo = includeCSSLibs
     }
     includeCSS {
       someIncludeFile = fileadmin/someIncludeFile2
       someIncludeFile.data-foo = includeCSS
     }
     includeJSLibs {
       someIncludeFile = fileadmin/someIncludeFile3
       someIncludeFile.data-consent-type = marketing
     }
     includeJS {
       someIncludeFile = fileadmin/someIncludeFile4
       someIncludeFile.data-consent-type = essential
     }
     includeJSFooterlibs {
       someIncludeFile = fileadmin/someIncludeFile5
       someIncludeFile.data-my-attribute = foo
     }
     includeJSFooter {
       someIncludeFile = fileadmin/someIncludeFile6
       someIncludeFile.data-foo = includeJSFooter
     }
   }

Reserved keywords which will not be mapped to attributes are:

- compress
- forceOnTop
- allWrap
- type (set automatically, depending on config.doctype)
- disableCompression
- excludeFromConcatenation
- external
- inline

Resulting HTML:

.. code-block:: html

   <head>
       <link rel="stylesheet" type="text/css" href="/typo3conf/ext/myext/Resources/Public/someIncludeFile1" media="all" data-foo="includeCSS">
       <link rel="stylesheet" type="text/css" href="/typo3conf/ext/myext/Resources/Public/someIncludeFile2" media="all" data-foo="includeCSSLibs">

       <script src="/typo3conf/ext/myext/Resources/Public/someIncludeFile3" data-consent-type="marketing"></script>
       <script src="/typo3conf/ext/myext/Resources/Public/someIncludeFile4" data-consent-type="essential"></script>
   </head>
   <body>
       <script src="/typo3conf/ext/myext/Resources/Public/someIncludeFile5" data-my-attribute="foo"></script>
       <script src="/typo3conf/ext/myext/Resources/Public/someIncludeFile6" data-foo="includeJSFooteribs"></script>
   </body>

.. index:: Frontend, TypoScript, ext:frontend
