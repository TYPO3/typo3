..  include:: /Includes.rst.txt

..  _for-editors:

===========
For Editors
===========

Target group: **Editors**

The internal notes are a useful feature for adding context and notes to
pages. It provides a way for users to document important information
related to specific pages.

Usage
=====

..  contents:: Table of Contents
    :depth: 2
    :local:

Adding a note
-------------
To add a note to a page:

#.  Log in to the TYPO3 backend as a user with appropriate permissions.

#.  Navigate to the page where you want to add a note.

#.  Click on :guilabel:`Create internal note for this page` .

    ..  figure:: /Images/sys_note_create.png
        :alt: Creating a new sys_note note
        :class: with-shadow

        Button to create a new internal note

#.  Create a new internal note, select the appropriate category and add
    the desired text content.

#.  Save the note.


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

..  note::
    Each category is represented by a distinct icon.

..  figure:: /Images/sys_note_backend_formular.png
    :alt: Creating a new internal note
    :class: with-shadow

    Create a new internal note

Describing the note
-------------------

Enter the title of your note in the :guilabel:`Subject` field and the
description in the :guilabel:`Message` field.

Activate the :guilabel:`Personal` toggle in the :guilabel:`Access` tab, if the
note should be displayed only for you.

..  figure:: /Images/sys_note_personal.png
    :alt: Personal field
    :class: with-shadow

    Using the :guilabel:`Personal` feature

How does the internal note look in the backend?
===============================================

When a backend user opens the corresponding page, they will see a box displaying
the internal note, if at least one is available. The various colors represent
the different categories.

..  figure:: /Images/sys_note_adding_note.png
    :alt: Different internal note categories
    :class: with-shadow

    Different internal note categories

..  note::
    After creating the note, you can see who created the note and the
    creation date of the note.
