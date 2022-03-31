.. include:: /Includes.rst.txt

======================================================
Deprecation: #92435 - StandaloneView for EmailFinisher
======================================================

See :issue:`92435`

Description
===========

The :php:`EmailFinisher` class of EXT:form was extended for the possibility to use
FluidEmail in TYPO3 v10. Therefore the previously used StandaloneView has now been marked as
deprecated along with the configuration option :yaml:`templatePathAndFilename`.


Impact
======

Using the StandaloneView will trigger a PHP :php:`E_USER_DEPRECATED` error. Using
:yaml:`templatePathAndFilename` for custom templates will also trigger a
PHP :php:`E_USER_DEPRECATED` error.


Affected Installations
======================

All installations not already using FluidEmail for the EXT:form EmailFinisher.


Migration
=========

Adjust your finisher configuration to use FluidEmail by setting :yaml:`useFluidEmail: true`.

Before:

.. code-block:: yaml

    finishers:
       -
         identifier: EmailToReceiver
         options:
           useFluidEmail: false

After:

.. code-block:: yaml

   finishers:
     -
       identifier: EmailToReceiver
       options:
         useFluidEmail: true

For custom templates, replace :yaml:`templatePathAndFilename` with :yaml:`templateName`
and :yaml:`templateRootPaths`.

Before:

.. code-block:: yaml

    finishersDefinition:
      EmailToReceiver:
        options:
          templatePathAndFilename: EXT:sitepackage/Resources/Private/Templates/Email/ContactForm.html

After:

.. code-block:: yaml

   finishersDefinition:
     EmailToReceiver:
       options:
         templateName: ContactForm
         templateRootPaths:
           100: 'EXT:sitepackage/Resources/Private/Templates/Email/'


.. index:: YAML, NotScanned, ext:form
