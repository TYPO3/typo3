.. include:: /Includes.rst.txt

.. _feature-102815-1704964154:

================================================================
Feature: #102815 - Support ApplicationContext in TypoScript data
================================================================

See :issue:`102815`

Description
===========

The new key :typoscript:`applicationcontext` is added for TypoScript's data
function.

The application context is now available through:

.. code-block:: typoscript

   if {
       value.data = applicationcontext
       equals = Production
   }

Impact
======

It is now possible to fetch the current application context as full blown
string from TypoScript's :typoscript:`data` function.

.. index:: TypoScript, ext:frontend
