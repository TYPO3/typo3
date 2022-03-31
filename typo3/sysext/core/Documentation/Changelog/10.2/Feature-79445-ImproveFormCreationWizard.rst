.. include:: /Includes.rst.txt

==============================================
Feature: #79445 - Improve form creation wizard
==============================================

See :issue:`79445`

Description
===========

Based on a concept - which has been created during the TYPO3 UX week - the form creation wizard has been improved
greatly. This results in a vast enhancement of the user experience. In detail the following changes have been
implemented:

* The user interface has been visually refurbished and rearranged.
* Step 3 has been removed, it just confirmed the successful form creation.
* Previous steps are now accessible.
* Steps now have descriptive labels like "Start" or "Finish!".

This is achieved with the new JavaScript module :js:`MultiStepWizard`.


Impact
======

Editors will find a vastly enhanced form creation wizard. The UI and UX of the wizard have been improved big time.

.. index:: Backend, ext:form
