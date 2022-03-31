.. include:: /Includes.rst.txt

=================================================================
Feature: #47006 - Extend the widget identifier with custom string
=================================================================

See :issue:`47006`

Description
===========

The parameter `customWidgetId` has been introduced for fluid widgets. This string is used in the widget identifier
in addition to the `nextWidgetNumber`.

The widget identifier is used to create the GET parameter names.

A good value for the `customWidgetId` is the {contentObjectData.uid} to ensure no collisions happen.

Example:

.. code-block:: none

   <f:widget.paginate customWidgetId="{contentObjectData.uid}" ...></f:widget.paginate>


Impact
======

Allows to use the same fluid widget more than once on one page in different content elements.

.. index:: Fluid
