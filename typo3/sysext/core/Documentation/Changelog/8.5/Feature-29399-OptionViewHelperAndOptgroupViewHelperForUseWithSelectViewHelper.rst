.. include:: ../../Includes.txt

=======================================================================================
Feature: #29399 - OptionViewHelper and OptgroupViewHelper for use with SelectViewHelper
=======================================================================================

See :issue:`29399`

Description
===========

Allows manually definition of all options and optgroups for
the `f:form.select` parent field as tag contents of the
select field. The added ViewHelpers are TagBasedViewHelpers
which means they support all standard HTML attributes.

Note that while tag content rendering is now supported,
it is **STILL** not possible to create :html:`<option>` tags
manually - you **HAVE** to use the form fields!

Example:

.. code-block:: html

    <f:form.select name="myproperty">
        <f:form.select.option value="1">Option one</f:form.select.option>
        <f:form.select.option value="2">Option two</f:form.select.option>
        <f:form.select.optgroup>
            <f:form.select.option value="3">Grouped option one</f:form.select.option>
            <f:form.select.option value="4">Grouped option twi</f:form.select.option>
        </f:form.select.optgroup>
    </f:form.select>


Impact
======

* Adds two new ViewHelpers
* Changes `SelectViewHelper` to allow tag content (but not manual options created without using `f:form.select.*`)

.. index:: Fluid
