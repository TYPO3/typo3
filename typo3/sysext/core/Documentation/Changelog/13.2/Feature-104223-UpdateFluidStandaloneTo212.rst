.. include:: /Includes.rst.txt

.. _feature-104223-1719417803:

==================================================
Feature: #104223 - Update Fluid Standalone to 2.12
==================================================

See :issue:`104223`

Description
===========

Fluid Standalone has been updated to version 2.12. This version adds new capabilities for
TagBasedViewHelpers as well as a new ViewHelper. The introduced XSD Schema Generator will
be adapted to TYPO3's Fluid integration in a separate patch.


Impact
======

All TagBasedViewHelpers (such as :html:`<f:image />` or :html:`<f:form.*>`) can now receive
arbitrary tag attributes which will be appended to the resulting HTML tag. In the past,
this was only possible for a small list of tag attributes, like class, id or lang.

.. code-block:: html

    <f:form.textfield inputmode="tel" />
    <f:image image="{image}" hidden="hidden" />

A :html:`<f:constant>` ViewHelper has been added to be able to access PHP constants from
Fluid templates:

.. code-block:: html

    {f:constant(name: 'PHP_INT_MAX')}
    {f:constant(name: '\Vendor\Package\Class::CONSTANT')}
    {f:constant(name: '\Vendor\Package\Enum::CASE')}

.. index:: Fluid, Frontend, ext:fluid
