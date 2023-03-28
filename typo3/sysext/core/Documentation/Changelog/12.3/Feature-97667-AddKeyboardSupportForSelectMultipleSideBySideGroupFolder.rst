.. include:: /Includes.rst.txt

.. _feature-97667-1678967840:

======================================================
Feature: #97667 - Add keyboard support for Multiselect
======================================================

See :issue:`97667`

Description
===========

You are able to use the keyboard for selecting and deselecting options in
Multiselect.

- :kbd:`Enter` adds options, either from right to left or left to right
- :kbd:`Delete` or :kbd:`Backspace` removes an option for windows/mac users
- :kbd:`Alt` + :kbd:`ArrowUp` moves the option one up
- :kbd:`Alt` + :kbd:`ArrowDown` moves the option one down
- :kbd:`Alt` + :kbd:`Shift` + :kbd:`ArrowUp` moves it to the top
- :kbd:`Alt` + :kbd:`Shift` + :kbd:`ArrowDown` moves it to the bottom

More combinations are possible by default:

- :kbd:`Shift` + :kbd:`ArrowUp` includes the upper option
- :kbd:`Shift` + :kbd:`ArrowDown` includes the lower option
- :kbd:`Home` moves the cursor to the top
- :kbd:`End` move the cursor to the bottom

Impact
======

This currently affects the following TCA configurations:

- :php:`'type' => 'select', 'renderType' => 'selectMultipleSideBySide'`
- :php:`'type' => 'group'`
- :php:`'type' => 'folder'`

.. index:: TCA, ext:backend
