
.. include:: /Includes.rst.txt

====================================================
Feature: #72045 - HTMLparser.stripEmptyTags.keepTags
====================================================

See :issue:`72045`

Description
===========

A new option for the `HTMLparser.stripEmptyTags` configuration is added.
It allows keeping configured tags. Before this change only a list of tags
could be provided that should be removed.

The following example will strip all empty tags **except** `tr` and `td` tags.

::

    HTMLparser.stripEmptyTags = 1
    HTMLparser.stripEmptyTags.keepTags = tr,td


**Important!** If this setting is used the `stripEmptyTags.tags` configuration will
have no effect any more. You can only use one option at a time.


Impact
======

Unless the configuration of the `HTMLparser is changed`, the stripEmptyTags
feature will work as before.

.. index:: Backend, TSConfig
