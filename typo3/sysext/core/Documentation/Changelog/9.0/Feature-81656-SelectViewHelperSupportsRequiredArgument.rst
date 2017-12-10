.. include:: ../../Includes.txt

===============================================================
Feature: #81656 - Select view helper supports required argument
===============================================================

See :issue:`81656`

Description
===========

The select view helper supports the optional argument :html:`required` according to this:
https://www.w3schools.com/tags/att_select_required.asp


Impact
======

Set the "required" argument like this:

.. code-block:: html

    <f:form.select property="category" required="1" options="{categories}" />

The rendered html output is this:

.. code-block:: html

    <select required="required" name="tx_ext[model][category]">
        <option value="...">...</option>
    </select>

.. index:: Fluid, Frontend, Backend
