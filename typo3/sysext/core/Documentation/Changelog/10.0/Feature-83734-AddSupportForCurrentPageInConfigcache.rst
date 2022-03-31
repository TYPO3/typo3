.. include:: /Includes.rst.txt

==============================================================
Feature: #83734 - Add support for current page in config.cache
==============================================================

See :issue:`83734`

Description
===========

When using the TypoScript property :typoscript:`config.cache`, it is possible to define a configuration that
affects all pages via:

.. code-block:: typoscript

   config.cache.all = fe_users:2

However such configurations always depend on a precise page where to look up records.
A common scenario is to have records stored in each page itself.
Thus, the syntax with the keyword "current" is now possible:

.. code-block:: typoscript

   config.cache.all = fe_users:current

where :typoscript:`current` is dynamically replaced by the current Page ID.


Impact
======

When using :typoscript:`current` inside the :typoscript:`config.cache` TypoScript property, it is now replaced with
the current Page ID.

.. index:: TypoScript
