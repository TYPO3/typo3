.. include:: /Includes.rst.txt

.. _feature-86880-1659742357:

=======================================================
Feature: #86880 - Enable password view at backend login
=======================================================

See :issue:`86880`

Description
===========

On clicking, the TYPO3 backend login now displays an additional button to reveal the user's
password, once something has been typed in the password field.

Impact
======

A user who is about to log in to the backend is now able to reveal the typed
password. Once the password field is cleared, the visibility mode automatically
switches back to its default to avoid revealing sensitive data by accident.

..  warning::
    Revealing login credentials is always a security risk. Please use this
    feature with caution when nobody can watch your input, either remotely or by
    looking over your shoulders!

.. index:: Backend, ext:backend
