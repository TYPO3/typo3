.. include:: /Includes.rst.txt

.. _feature-95486:

==========================================================
Feature: #95486 - Add accept argument for UploadViewHelper
==========================================================

See :issue:`95486`

Description
===========

It is now possible to pass file types via "accept" as argument directly to the
UploadViewHelper.
Previously this had to be done by using "additionalAttributes".
This way it can be defined which file types are allowed for uploads,
to prevent unwanted file formats.

Example
=======

..  code-block:: html

    <f:form.upload accept=".jpg,.png" />

.. index:: Fluid, ext:fluid
