.. include:: /Includes.rst.txt

.. _feature-97667-1678967840:

==================================================================================
Feature: #97667 - Add keyboard support for Multiselect
==================================================================================

See :issue:`97667`

Description
===========

You are able to use the keyboard for selecting and deselecting options in
Multiselect.

- 'Enter' adds options, either from right to left or left to right
- 'Delete' or 'Backspace' removes an option for windows/mac users
- 'Alt' + 'ArrowUp' moves the option one up
- 'Alt' + 'ArrowDown' moves the option one down
- 'Alt' + 'Shift' + 'ArrowUp' moves it to the top
- 'Alt' + 'Shift' + 'ArrowDown' moves it to the bottom

More combinations are possible by default:

- 'Shift' + 'ArrowUp' includes the upper option
- 'Shift' + 'ArrowDown' includes the lower option
- 'Home' moves the cursor to the top
- 'End' move the cursor to the bottom

Impact
======

This currently effects the following TCA configurations:

- :php:`'type' => 'select', 'renderType' => 'selectMultipleSideBySide'`
- :php:`'type' => 'group'`
- :php:`'type' => 'folder'`

.. index:: TCA, ext:backend
