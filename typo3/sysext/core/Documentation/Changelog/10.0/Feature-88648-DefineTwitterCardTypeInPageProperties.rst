.. include:: /Includes.rst.txt

==========================================================
Feature: #88648 - Set Twitter Card Type in page properties
==========================================================

See :issue:`88648`

Description
===========

It is now possible to select the type of Twitter Card to be shown when a page is shared
on Twitter. This option will render the :html:`twitter:card` meta tag in frontend.

Impact
======

If you manually changed the value of the :html:`twitter:card` by for example TypoScript and you
want to override the value of the page properties, you have to use the replace option to
override this value from the page properties.

Example:

.. code-block:: typoscript

  page {
    meta {
      twitter:card = summary_large_image
      twitter:card.replace = 1
    }
  }


.. index:: ext:seo, Frontend, TypoScript
