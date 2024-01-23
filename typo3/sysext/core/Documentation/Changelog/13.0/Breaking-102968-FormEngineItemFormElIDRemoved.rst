.. include:: /Includes.rst.txt

.. _breaking-102968-1706440705:

===================================================
Breaking: #102968 - FormEngine itemFormElID removed
===================================================

See :issue:`102968`

Description
===========

When dealing with custom FormEngine elements in the backend record editing
interface, the infrastructure prepares a huge data array and hands it over
to single element classes for rendering.

The specific data key :php:`$this->data['parameterArray']['itemFormElID']`
has been removed. The intention of that key was to prepare some unique id
to be used as :html:`id` attribute. This never made a lot of sense, single
element classes can easily take care of this on their own if needed.

Since the Core can't actively deprecate and log access to members of the main
data array as such, there is no point in declaring a deprecation for it, and
the array entry has been removed directly.


Impact
======

Extensions with custom backend FormEngine elements may raise an
"undefined array key" PHP warning, or may create empty id attributes in
their HTML output if accessing :php:`itemFormElID`.


Affected installations
======================

Instances with extensions that deliver custom FormEngine elements may
be affected.


Migration
=========

A typical use case for a unique :html:`id` attribute on a form element is to
connect it with a :html:`label` element. Accessing :php:`itemFormElID` can
usually be easily avoided by creating a unique string using
:php:`StringUtility::getUniqueId()`, with a custom prefix:

.. code-block:: php

    // Before
    $attributeId = htmlspecialchars($this->data['parameterArray']['itemFormElID']);
    $html[] = '<input id="' . $attributeId . '">';

    // After
    $attributeId = htmlspecialchars(StringUtility::getUniqueId('formengine-my-custom-element-'));
    $html[] = '<input id="' . $attributeId . '">';


.. index:: Backend, PHP-API, NotScanned, ext:backend
