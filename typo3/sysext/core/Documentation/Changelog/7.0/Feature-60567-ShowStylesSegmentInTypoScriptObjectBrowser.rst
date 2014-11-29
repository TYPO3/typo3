==========================================================
Feature: #60567 - Show Styles Segment in TS Object Browser
==========================================================

Description
===========

The TypoScript Object Browser now shows the setup segment :ts:`styles.`


Impact
======

The segment is cached in the Frontend and not unset anymore, page cache entries increase slightly in size.
