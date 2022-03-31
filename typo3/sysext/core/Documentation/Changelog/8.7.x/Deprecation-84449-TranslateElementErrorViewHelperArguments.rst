.. include:: /Includes.rst.txt

===============================================================
Deprecation: #84449 - TranslateElementErrorViewHelper arguments
===============================================================

See :issue:`84449`

Description
===========

The template EXT:form/Resources/Private/Frontend/Partials/Field/Field.html has been changed. This
was necessary because of a bug with validation messages containing arguments.
For compatibility reasons, the old template syntax is still supported but is deprecated and will be
removed with TYPO3 v10.

Impact
======

If a user utilizes his own template for EXT:form/Resources/Private/Frontend/Partials/Field/Field.html,
a deprecation warning will be thrown.


Affected Installations
======================

Any installation which uses the form framework and a customized template for
EXT:form/Resources/Private/Frontend/Partials/Field/Field.html.


Migration
=========

Change

.. code-block:: html

   {formvh:translateElementError(element: element, code: error.code, arguments: error.arguments, defaultValue: error.message)}

to

.. code-block:: html

   {formvh:translateElementError(element: element, error: error)}


.. index:: Frontend, ext:form
