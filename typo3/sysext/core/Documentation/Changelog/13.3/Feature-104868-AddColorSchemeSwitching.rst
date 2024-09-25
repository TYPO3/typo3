.. include:: /Includes.rst.txt

.. _feature-104868-1725912804:

=============================================
Feature: #104868 - Add color scheme switching
=============================================

See :issue:`104868`

Description
===========

Options have been added to switch between the available color schemes in TYPO3. A set of buttons
for each available color scheme in the user dropdown at the top right and a setting in User Settings.

As the dark color scheme is currently regarded experimental until further notice, color scheme switching logic is
currently hidden behind the UserTS setting :typoscript:`setup.fields.colorScheme.disabled`.

Impact
======

..  warning::
    If you don't want the automatic switching and don't include the `setup` core extension in your environment,
    you need to manually disable the feature yourself using the UserTS configuration
    :typoscript:`setup.fields.colorScheme.disabled = 1`!

It is now possible to switch to an automatic, light or dark color scheme for use in the backend.

.. index:: Backend, ext:backend
