.. include:: /Includes.rst.txt

=================================================================
Feature: #92460 - Split default from all languages in page module
=================================================================

See :issue:`92460`

Description
===========

The view "Languages" in the page module allows to display content elements of
the default language next to the ones of the selected language.

If the default language is chosen, instead of rendering all content elements
of all languages, now only the content elements of the default language are
rendered.

If an editor requires to see all content elements of all languages, the
option "All languages" can be selected.


Impact
======

Having many languages and many content elements can be a performance issue
in the page module which is now fixed.

Additionally the language view is now consistent with the column view.

.. index:: Backend, ext:backend
