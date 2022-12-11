.. include:: /Includes.rst.txt

.. _feature-99340-1672135447:

====================================================================
Feature: #99340 - Add StdWrap properties to config.additionalHeaders
====================================================================

See :issue:`99340`

Description
===========

StdWrap properties have been added to the `header` directive in `config.additionalHeaders`.

TypoScript examples
-------------------

.. code-block:: typoscript

   config.additionalHeaders {
       10 {
           header = link: <{path : EXT:site/Resources/Public/Fonts/icon.woff2}>; rel=preload; as=font; crossorigin
           header.insertData = 1
       }

       20 {
           header.data = path : EXT:site/Resources/Public/Fonts/gothic.woff2
           header.wrap = link: <|>; rel=preload; as=font; crossorigin
           replace = 0
       }
   }

Impact
======

It is now possible to sent dynamic HTTP headers with TypoScript, especially headers that contain paths to extension files.

.. index:: TypoScript, Frontend
