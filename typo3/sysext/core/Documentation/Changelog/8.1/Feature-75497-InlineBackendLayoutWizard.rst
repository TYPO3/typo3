
.. include:: ../../Includes.txt

==============================================
Feature: #75497 - inline backend layout wizard
==============================================

See :issue:`75497`

Description
===========

A new renderType was added to render the backend layout wizard inline in FormEngine.


Impact
======

The old `BackendLayoutWizardController` which has rendered the backend layout wizard in a popup has been removed.

Use the new renderType `belayoutwizard` to render the backend layout wizard inline in FormEngine.

example TCA configuration:

.. code-block:: php

   'config' => array(
      'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:backend_layout.config',
      'config' => array(
         'type' => 'text',
         'renderType' => 'belayoutwizard',
      )
   )

.. index:: TCA, Backend
