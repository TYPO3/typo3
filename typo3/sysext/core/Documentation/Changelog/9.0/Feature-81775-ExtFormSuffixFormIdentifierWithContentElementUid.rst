.. include:: /Includes.rst.txt

=====================================================================
Feature: #81775 - suffix form identifier with the content element uid
=====================================================================

See :issue:`81775`

Description
===========

Append the suffix "-$contentElementUid" (e.g. "myForm-65") to the form identifier
if the form is rendered through the `form` content element.
This makes it possible to use the same form multiple times on one page.

Impact
======

Now it is possible to use the same form multiple times on one page.

.. index:: Frontend, ext:form
