.. include:: /Includes.rst.txt

=======================================================
Feature: #89650 - Allow line breaks in TCA descriptions
=======================================================

See :issue:`89650`

Description
===========

TCA description texts are passed through :php:`nl2br()` to allow line breaks which make longer description texts easier to read.

Impact
======


To make use of this feature simply format your description text with new lines. Those will be converted to :html:`<br>` tags when creating the output.
Installations that make use of TCA descriptions heavily might want to double check the formatting of those texts to avoid unwanted new lines.

.. index:: Backend, ext:backend
