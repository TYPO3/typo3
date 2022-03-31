.. include:: /Includes.rst.txt

======================================================================
Feature: #77757 - Enable rechecking whether an UpdateWizard should run
======================================================================

See :issue:`77757`

Description
===========

It is now possible to reset the upgrade wizards marked as done. In Install Tool you
will find a list of wizards that have been marked as done, additionally with a
checkbox for each to reset their status. Then the wizard will test whether
it needs to be executed again.

.. index:: Backend
