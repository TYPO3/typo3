.. include:: /Includes.rst.txt

.. _feature-104223-1719417803:

==================================================
Feature: #104223 - Update Fluid Standalone to 2.12
==================================================

See :issue:`104223`

Description
===========

Fluid Standalone has been updated to version 2.12. This version adds new capabilities for
tab based ViewHelpers and adds the new ViewHelper :html:`f:constant`.

Also see this :ref:`deprecation document<deprecation-104223-1721383576>` for
information on deprecated functionality.


Impact
======

Arbitrary tags with tag based view helpers
------------------------------------------

Tag based view helpers (such as :html:`<f:image />` or :html:`<f:form.*>`) can now
receive arbitrary tag attributes which will be appended to the resulting HTML tag,
without dedicated registration.

.. code-block:: html

    <f:form.textfield inputmode="tel" />
    <f:image image="{image}" hidden="hidden" />


New f:constant ViewHelper
-------------------------

A :html:`<f:constant>` ViewHelper has been added to be able to access PHP constants from
Fluid templates:

.. code-block:: html

    {f:constant(name: 'PHP_INT_MAX')}
    {f:constant(name: '\Vendor\Package\Class::CONSTANT')}
    {f:constant(name: '\Vendor\Package\Enum::CASE')}

.. index:: Fluid, Frontend, ext:fluid
