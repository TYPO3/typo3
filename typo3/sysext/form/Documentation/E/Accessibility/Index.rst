.. include:: /Includes.rst.txt

.. _accessibility:

=============
Accessibility
=============

There are numerous accessibility rules when it comes to making a form accessible
to a large number of users. This includes accessibility to groups such as people
with a disability, the elderly, non-native speakers, etc.

The following should be kept in mind by editors creating forms
in the backend form editor:

Labels
======

Always use clear, descriptive labels in the :guilabel:`Label` field. Simply
putting the label in field :guilabel:`Placeholder` is not
considered accessible.

Descriptions
============

Add an extended description in the field :guilabel:`Description`.

Placeholder
===========

The :guilabel:`Placeholder` field should not contain the label.
It should contain example content to make filling out the field easier for
users.

Autocomplete
============

The autocomplete property should be used whenever a field contains personal
information. This property can then be used by assistive
technology to aid users to fill out forms. Select the desired purpose from the
select :guilabel:`Autocomplete`. See `Input Purposes for User Interface
Components at w3.org <https://www.w3.org/TR/WCAG21/#input-purposes>`__ for
an explanation of which purposes to use.

If additional input purposes are needed, your integrator or developer can
:ref:`add additional input purpose options <concepts-autocomplete-add-options>`.
