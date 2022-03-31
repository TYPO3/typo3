.. include:: /Includes.rst.txt

==============================================================
Feature: #89227 - Ask for email address while installing TYPO3
==============================================================

See :issue:`89227`

Description
===========

It is now possible to enter an email address for the first admin user while installing TYPO3.

Within the install process, an admin user will be created by entering a username and password.
The user is asked for the email address as well, so this can be used
later on to e.g. notify the admin if somebody logged-in (Warning email address).

This will also work in the install tool Maintenance module "Create Administrative User" card.

.. index:: Backend, ext:install
