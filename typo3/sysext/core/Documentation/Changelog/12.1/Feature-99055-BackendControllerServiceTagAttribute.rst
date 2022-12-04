.. include:: /Includes.rst.txt

.. _feature-99055-1668096727:

=========================================================
Feature: #99055 - BackendController service tag attribute
=========================================================

See :issue:`99055`

Description
===========

A new PHP attribute :php:`TYPO3\CMS\Backend\Attribute\Controller` has
been added in order to register services to the BackendController dependency
injection container.

In addition to tag :yaml:`backend.controller` in the :file:`Services.yaml` file,
tagging services as backend controller can be done like:

Example implementation
----------------------

..  code-block:: php

    use TYPO3\CMS\Backend\Attribute\Controller;

    #[Controller]
    class MyBackendController {

    }

Impact
======

It is now possible to tag services as backend controller by the PHP attribute
:php:`TYPO3\CMS\Backend\Attribute\Controller` instead of tagging them with
:yaml:`backend.controller` in the :file:`Services.yaml` file.

.. index:: Backend
