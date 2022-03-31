.. include:: /Includes.rst.txt

======================================
Feature: #79445 - Add Multistep Wizard
======================================

See :issue:`79445`

Description
===========

Based on a concept - which has been created during the TYPO3 UX week - a new JavaScript module :js:`MultiStepWizard` has been introduced.

Compared to the existing :js:`Wizard` - which is currently in use e.g. when translating content or creating a new form - the following changes have been implemented:

* Navigation to previous steps is possible.
* Instead of labeling steps with just a numerical indicator (like "Step x of y") steps can have descriptive labels like "Start" or "Finish!".
* The structure of the configuration has been optimized.

Code examples:

.. code-block:: js

   // Show/ hide the wizard
   MultiStepWizard.show();
   MultiStepWizard.dismiss();

   // Add a slide to the wizard
   MultiStepWizard.addSlide(
       identifier,
       stepTitle,
       content,
       severity,
       progressBarTitle,
       function() {
       ...
       }
   );

   // Lock/ unlock navigation buttons
   MultiStepWizard.lockNextStep();
   MultiStepWizard.unlockNextStep();
   MultiStepWizard.lockPrevStep();
   MultiStepWizard.unlockPrevStep();


Impact
======

Developers can provide editors with a vastly enhanced wizard. The UI and UX of the wizard have been improved big time.

.. index:: Backend, JavaScript, ext:backend
