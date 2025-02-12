..  include:: /Includes.rst.txt

..  _for-editors:

===========
For Editors
===========

Target group: **Editors**

The internal notes are a useful feature for adding context and notes to
pages. It provides a way for users to document important information
related to specific pages.

..  contents:: Table of Contents

..  _for-editors-usage:

Usage
=====

..  _for-editors-add-note:

Adding a note
-------------

To add a note to a page:

#.  Log in to the TYPO3 backend as a user with appropriate permissions.

#.  Navigate to the page where you want to add a note.

#.  Click on :guilabel:`Create internal note for this page` .

    ..  figure:: /Images/sys_note_create.png
        :alt: Screenshot demonstrating the location of the "Create internal note" button in the module header of the Page module

        The button to create a system not is located on the top right of the "Page" and "List" modules

#.  Create a new internal note, select the appropriate category and add
    the desired text content.

#.  Save the note.


..  _for-editors-categories:

Categories
----------

You can choose between:

Instructions
    Used to provide instructions to the backend user.

Notes
    Used for simple notes.

To-Do
    Used to allow a backend user to see and complete pending to-dos.

Template
    Used to output a template.

..  figure:: /Images/sys_note_backend_formular.png
    :alt: Creating a new internal note

    Create a new internal note

..  _for-editors-description:

Describing the note
-------------------

Enter the title of your note in the :guilabel:`Subject` field and the
description in the :guilabel:`Message` field.

Activate the :guilabel:`Personal` toggle in the :guilabel:`Access` tab, if the
note should be displayed only for you.


..  _for-editors-output:

How does the internal note look in the backend?
===============================================

A System note is displayed on the top or bottom (depending on which option was
activated in the record) of the modules "Page" and "List" when viewing the
page in question:

..  figure:: /Images/sys_note_output.png
    :alt: Screenshot of a TODO note on the top of the "Page" backend module

    If you have sufficient permissions you can edit or delete the note.
