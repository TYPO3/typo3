.. include:: ../../Includes.txt

======================================================================
Feature: #77757 - Enable rechecking whether an UpdateWizard should run
======================================================================

See :issue:`77757`

Description
===========

It is now possible to reset the upgrade wizards marked as done. In Install Tool you will find a list of wizards that has been marked as done, additionally with a checkbox for each to reset this mark. Then the wizard will be tested again, whether it needs to be executed again.

.. index:: Backend