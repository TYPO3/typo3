.. include:: ../../Includes.txt

=================================================================
Deprecation: #80076 - TypoScript option page.insertClassesFromRTE
=================================================================

See :issue:`80076`

Description
===========

The TypoScript setting :typoscript:`page.insertClassesFromRTE` has been marked as deprecated.

The option enabled loading of CSS classes defined by backend PageTs config :typoscript:`RTE.classes` as inline CSS
into a frontend page. However it did not take merged RTE options and userTS/pageTS overrides
into account.


Impact
======

Setting :typoscript:`page.insertClassesFromRTE` in TypoScript will trigger a deprecation log entry.


Affected Installations
======================

Any installation having the option activated in TypoScript.


Migration
=========

In order to separate the functionality, all CSS classes which have been used in the RTE should be
defined separately for the frontend rendering in a custom CSS/LESS/SASS file.

.. index:: TypoScript, Frontend, RTE
