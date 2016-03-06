.. include:: ../../Includes.txt

=============================================================================
Feature: #47135 - Paste icons available at pasting position and use modal now
=============================================================================

See :issue:`47135`

Description
===========

As soon as the normal clipboard contains an item, there will be a single paste icon available in the page module.
The icon will be located at each possible pasting position directly besides the [content +] buttons.
When the user clicks on the icon, a modal pops up to have the user confirm the action.
Depending on the clibpoard mode this will either be "Copy" or "Move" together with the title of the item in the clipboard and a "Cancel" button.

.. index:: Backend, JavaScript