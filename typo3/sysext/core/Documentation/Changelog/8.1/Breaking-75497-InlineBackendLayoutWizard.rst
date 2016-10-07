
.. include:: ../../Includes.txt

===============================================
Breaking: #75497 - inline backend layout wizard
===============================================

See :issue:`75497`

Description
===========

The `BackendLayoutWizardController` has been removed and a new renderType has been added to render the backend layout wizard inline in FormEngine.

Also the backend route `wizard_backend_layout` has been removed.


Impact
======

Extending or using the `BackendLayoutWizardController` will break installations.


Affected Installations
======================

Any installation which uses an extension which makes use of `BackendLayoutWizardController`


Migration
=========

Use the renderType `belayoutwizard`, which renders the backend layout wizard inline in FormEngine.

.. index:: PHP-API, Backend, TCA