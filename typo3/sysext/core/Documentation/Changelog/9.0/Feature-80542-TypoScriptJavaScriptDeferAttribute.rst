.. include:: ../../Includes.txt

===============================================================================
Feature: #80542 - Support defer attribute for JavaScript includes in TypoScript
===============================================================================

See :issue:`80542`

Description
===========

When including JavaScript files in TypoScript, the HTML5 attribute :html:`defer` is now
supported.

.. code-block:: typoscript

   page.includeJSFooter.file = path/to/file.js
   page.includeJSFooter.file.defer = 1


.. index:: TypoScript, Frontend
