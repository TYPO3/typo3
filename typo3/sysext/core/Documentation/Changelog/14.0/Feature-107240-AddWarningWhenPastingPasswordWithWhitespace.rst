.. include:: /Includes.rst.txt

.. _feature-107240-1739485792:

====================================================================
Feature: #107240 - Add warning when pasting password with whitespace
====================================================================

See :issue:`107240`

Description
===========

A new warning mechanism has been introduced in the backend login form to help
users avoid authentication issues when pasting passwords that contain leading
or trailing whitespace.

When a password is pasted into the backend login form, the system now detects
if the pasted text contains leading or trailing whitespace characters (spaces,
tabs, newlines, etc.) and displays a warning message to the user.

The warning includes an action button that allows users to automatically remove
the surrounding whitespace from the pasted password, ensuring successful login
attempts.

This feature helps prevent common login failures caused by accidentally copying
whitespace along with passwords from password managers, text editors, or other
sources.

The implementation includes:

- Detection of leading and trailing whitespace in pasted passwords
- Visual warning message displayed to the user
- One-click action to remove the surrounding whitespace

Example
=======

The whitespace detection covers various whitespace characters including:

- Regular spaces (U+0020)
- Tabs (U+0009)
- Line breaks (U+000A, U+000D)
- Other Unicode whitespace characters

Impact
======

Users will now receive immediate feedback when pasting passwords with
surrounding whitespace, reducing login failures and improving the overall
user experience when authenticating to the TYPO3 backend.

.. index:: Backend, JavaScript, UX, ext:backend
