.. include:: /Includes.rst.txt

.. _deprecation-103230-1709202638:

===========================================================
Deprecation: #103230 - Deprecate `@typo3/backend/wizard.js`
===========================================================

See :issue:`103230`

Description
===========

The TYPO3 backend module :js:`@typo3/backend/wizard.js` that offers simple
wizards has been marked as deprecated in favor of the richer
:js:`@typo3/backend/multi-step-wizard.js` module.


Impact
======

Using the deprecated module will trigger a browser console warning.


Affected installations
======================

All installations using :js:`@typo3/backend/wizard.js` are affected.


Migration
=========

Migrate to the module :js:`@typo3/backend/multi-step-wizard.js`. There are two
major differences:

* The class name changes to :js:`MultiStepWizard`.
* The method :js:`addSlide()` receives an additional argument for the step title
  in the progress bar.


Example
-------

..  code-block:: diff

    -import Wizard from '@typo3/backend/wizard.js';
    +import MultiStepWizard from '@typo3/backend/multi-step-wizard.js';

    -Wizard.addSlide(
    +MultiStepWizard.addSlide(
         'my-slide-identifier',
         'Slide title',
         'Content of my slide',
         SeverityEnum.notice,
    +    'My step',
         function () {
             // callback executed after displaying the slide
         }
    );

.. index:: Backend, JavaScript, NotScanned, ext:backend
