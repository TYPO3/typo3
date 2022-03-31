.. include:: /Includes.rst.txt

============================================================
Feature: #90213 - Support 'bit and' in TypoScript stdWrap_if
============================================================

See :issue:`90213`

Description
===========

It is now possible to use :typoscript:`bitAnd` within TypoScript :typoscript:`if`.

TYPO3 uses bits to store radio and checkboxes via TCA.
Without this feature one would need to check whether any possible bit value is in a
list. With this feature a simple comparison whether the expected value is part of the
bit set is possible.

Example
=======

An example usage could look like this:

.. code-block:: typoscript

   hideDefaultLanguageOfPage = TEXT
   hideDefaultLanguageOfPage {
     value = 0
     value {
       override = 1
       override.if {
         bitAnd.field = l18n_cfg
         value = 1
       }
     }
   }

.. index:: ext:frontend, TypoScript
