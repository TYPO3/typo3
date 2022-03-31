
.. include:: /Includes.rst.txt

====================================================================
Deprecation: #76104 - Deprecated single slash comments in TypoScript
====================================================================

See :issue:`76104`

Description
===========

Double slash one-line comments are standard in many languages.
Make them standard for TypoScript, too.

Define::

   One-line comments must start with two forward slashes as
   the first non-blank characters and should be followed by
   a whitespace.


Deprecated::

   / Line comment headed by single slash


Impact
======

The TypoScript developer receives a deprecation warning
with line number.


Affected Installations
======================

Installations using a single slash to indicate a comment.


Migration
=========

Use double slashes (//) or hash (#) for comments in Typoscript.

.. index:: TypoScript, Frontend
