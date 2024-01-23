.. include:: /Includes.rst.txt

.. _breaking-101647-1691669710:

====================================================================
Breaking: #101647 - Unused graphical assets removed from EXT:backend
====================================================================

See :issue:`101647`

Description
===========

The TYPO3 system extension "backend" accumulated many graphical assets over the
years that became unused piece by piece.

The following icons have been removed from the Icon Registry:

* `status-edit-read-only`
* `warning-in-use`
* `warning-lock`

The following assets have been removed from the directory :file:`EXT:backend/Resources/Public/Images/`:

* :file:`FormFieldWizard/wizard_forms.gif`
* :file:`clear.gif`
* :file:`filetree-folder-default.png`
* :file:`filetree-folder-opened.png`
* :file:`Logo.png`
* :file:`pages.gif`
* :file:`tt_content.gif`


Impact
======

Calling any of the removed icons from the Icon Registry will render the default
icon. Accessing any of the removed files directly will lead to a 404 error.


Affected installations
======================

All extensions using the removed icons and assets are affected.


Migration
=========

No direct migration is available.

.. index:: Backend, NotScanned, ext:backend
