.. include:: /Includes.rst.txt

.. _feature-102631-1702031335:

========================================================================================
Feature: #102631 - Introduce AsController attribute to autoconfigure backend controllers
========================================================================================

See :issue:`102631`

Description
===========

A new custom PHP attribute :php:`\TYPO3\CMS\Backend\Attribute\AsController` has
been introduced in order to automatically tag backend controllers, making them
available in the service container and enabling dependency injection.

Instead of adding the :yaml:`backend.controller` manually, the attribute
can be set on the class:

..  code-block:: php

    use TYPO3\CMS\Backend\Attribute\AsController;

    #[AsController]
    class MyBackendController {

    }

.. note::

    The attribute is a drop-in replacement for the
    :ref:`deprecated <deprecation-102631-1702031387>`
    :php:`\TYPO3\CMS\Core\Attribute\Controller` attribute.

Impact
======

It's now possible to automatically tag a backend controller by adding the new
PHP attribute :php:`\TYPO3\CMS\Backend\Attribute\AsController`.

.. index:: Backend, PHP-API, ext:backend
