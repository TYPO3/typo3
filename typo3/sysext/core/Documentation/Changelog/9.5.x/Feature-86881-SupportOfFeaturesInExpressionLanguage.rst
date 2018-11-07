.. include:: ../../Includes.txt

============================================================
Feature: #86881 - Support of Features in expression language
============================================================

See :issue:`86881`

Description
===========

With #83429 the core got support for feature toggles including a small API.
This patch adds support for feature toggle check in the symfony expression language DefaultFunctionProvider.
With the new function :typoscript:`feature()` the feature toggle can be checked.

.. code-block:: typoscript

   [feature("TypoScript.strictSyntax")]
   # This condition matches if the feature toggle "TypoScript.strictSyntax" is true
   [END]

   [feature("TypoScript.strictSyntax") === false]
   # This condition matches if the feature toggle "TypoScript.strictSyntax" is false
   [END]


.. index:: Backend, Frontend, TypoScript, ext:core, NotScanned
