.. include:: /Includes.rst.txt

.. _feature-97778-1655732248:

===========================================================
Feature: #97778 - Support of language direction in ckeditor
===========================================================

See :issue:`97778`

Description
===========

The configuration `contentsLangDirection` of the ckeditor is used to define the
direction of the content. It is now filled by the direction defined in the site
language of the current element.

As fallback the page TSconfig configuration :typoscript:`RTE.config.contentsLanguageDirection = rtl`
can be used.

Impact
======

The direction of the content inside the RichText element is defined by the
language of record.

.. index:: Backend, RTE, ext:rte_ckeditor
