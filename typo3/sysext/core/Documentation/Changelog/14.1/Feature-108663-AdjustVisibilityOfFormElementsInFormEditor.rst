..  include:: /Includes.rst.txt

..  _feature-108663-1737166800:

====================================================================
Feature: #108663 - Adjust visibility of form elements in Form Editor
====================================================================

See :issue:`108663`

Description
===========

The Form Editor now provides the ability to configure the visibility of form
elements. This allows form administrators to control which form elements are
displayed or hidden in the form, providing better control over the form structure
and user experience.

Usage
-----

When editing a form element in the Form Editor, a new "Visibility" option is
available in the element's configuration panel. This option allows you to:

* Show the element (default behavior)
* Hide the element

The visibility setting is stored in the form definition and is evaluated when
the form is rendered on the frontend.

Impact
======

This feature improves the usability of the Form Editor and makes it more accessible
to non-technical users who need to manage form visibility.

..  index:: Backend, ext:form

