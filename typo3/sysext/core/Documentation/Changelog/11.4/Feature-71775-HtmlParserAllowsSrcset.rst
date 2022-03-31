.. include:: /Includes.rst.txt

==========================================
Feature: #71775 - HtmlParser allows srcset
==========================================

See :issue:`71775`

Description
===========

The :php:`\TYPO3\CMS\Core\Html\HtmlParser` - most commonly used when rendering RTE fields
in the frontend - now handles the :html:`srcset` attribute.

A casual use case for this are responsive images:

.. code-block:: html

   <picture>
       <source media="(max-width: 799px)" srcset="small-image.jpg">
       <source media="(min-width: 800px)" srcset="larger-image.jpg">
   </picture>


Impact
======

Using :html:`source` tag with :html:`srcset` attribute is allowed and
:html:`srcset` values are prefixed correctly.

.. index:: Frontend, RTE, ext:core
