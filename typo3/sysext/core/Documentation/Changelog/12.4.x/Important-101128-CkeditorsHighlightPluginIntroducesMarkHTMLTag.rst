.. include:: /Includes.rst.txt

.. _important-101128-1723726464:

===========================================================================
Important: #101128 - CKEditor's highlight plugin introduces `mark` HTML tag
===========================================================================

See :issue:`101128`

Description
===========

The introduction of the CKEditor plugin :js:`@ckeditor/ckeditor5-language`
allows an editor to use the :html:`mark` tag, as well as the :html:`s` tag.

It may become necessary to explicitly allow this tag in the
:typoscript:`lib.parseFunc_RTE` TypoScript setup to allow the tag to be
rendered properly in the frontend:

..  code-block:: typoscript

    lib.parseFunc_RTE {
      allowTags := addToList(mark,s)
    }

Custom CSS styling for different markers classes needs to be
implemented in a sitepackage for example, as no frontend
CSS for this is emitted by default.

.. index:: Frontend, RTE, TSConfig, ext:core
