
.. include:: ../../Includes.txt

================================================================
Breaking: #70574 - Form Wizard Save Handling Changed in ext:form
================================================================

See :issue:`70574`

Description
===========

The ExtJS wizard of EXT:form in the backend has been using an old "module" to load and to save the data from the wizard,
but has been misusing this functionality as AJAX responses.
All AJAX requests for the wizard are now built with AJAX Routes and PSR-7-based Request/Response objects.

All obsolete WizardView PHP classes have been removed without substitution:

* \TYPO3\CMS\Form\View\Wizard\AbstractWizardView
* \TYPO3\CMS\Form\View\Wizard\LoadWizardView
* \TYPO3\CMS\Form\View\Wizard\SaveWizardView


Impact
======

Using these now non-existent PHP classes will result in fatal errors or wrong results when calling them directly.


Affected Installations
======================

Any installations with extensions that hook into the wizard views of EXT:form.


Migration
=========

Use the AJAX routes available via `TYPO3.settings.ajaxUrls['formwizard_load']` and `TYPO3.settings.ajaxUrls['formwizard_save']`.


.. index:: PHP-API, ext:form
