.. include:: /Includes.rst.txt

.. _accessibility:

=============
Accessibility
=============

There a numerous rules concerning accessibility that need to be addressed to
make a form accessible to a large number of users. This includes accessibility
for handicaped persons, the elderly, non-native speakers etc.

The following topics need to be addressed by the editors that create the form
in the backend form editor:

Labels
======

Always use clear, descriptive labels in the field :guilabel:`Label`. Setting
the label only in field :guilabel:`Placeholder` is not
considered accessible.

Descriptions
============

You can add an extended description in the field :guilabel:`Description`.

Placeholder
===========

The field :guilabel:`Placeholder` may not be used for the fields label. It
can however show some example content to make filling out the field easier for
users.

Autocomplete
============

The property autocomplete should be used whenever personal information should
be entered into the field. This property can then be used by assistive
technology to aid users fill out forms. Select the desired purpose from the
select :guilabel:`Autocomplete`. See `Input Purposes for User Interface
Components at w3.org <https://www.w3.org/TR/WCAG21/#input-purposes>`__ for
an explanation which purposes to use.

If additional input purposes are needed, your integrator or developer can
:ref:`add additional input purpose options <concepts-autocomplete-add-options>`.
