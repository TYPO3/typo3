
.. include:: ../../Includes.txt

==========================================================
Feature: #60567 - Show Styles Segment in TS Object Browser
==========================================================

See :issue:`60567`

Description
===========

The TypoScript Object Browser now shows the setup segment :code:`styles.`


Impact
======

The segment is cached in the Frontend and not unset anymore, page cache entries increase slightly in size.
