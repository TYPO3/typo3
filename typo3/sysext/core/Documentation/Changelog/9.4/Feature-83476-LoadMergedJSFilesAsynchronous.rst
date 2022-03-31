.. include:: /Includes.rst.txt

===================================================
Feature: #83476 - Load merged JS files asynchronous
===================================================

See :issue:`83476`

Description
===========

The async attribute is now assigned to the script tag of the concatenated JS files if all files have the async attribute enabled in TypoScript.

Example:
--------

.. code-block:: typoscript

   config.concatenateJs = 1

   page = PAGE
   page.includeJSFooter {
       test = fileadmin/user_upload/test.js
       test.async = 1

       test2 = fileadmin/user_upload/test2.js
       test2.async = 1
   }

.. index:: Frontend, TypoScript, ext:core
