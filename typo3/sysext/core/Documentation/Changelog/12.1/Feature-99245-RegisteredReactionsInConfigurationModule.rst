.. include:: /Includes.rst.txt

.. _feature-99245-1669974318:

==============================================================
Feature: #99245 - Registered reactions in configuration module
==============================================================

See :issue:`99245`

Description
===========

With :issue:`98373`, the new reactions component has been introduced to TYPO3
Core. Since reactions allow to hook into the system, it's important for site
administrators to have an overview of registered reactions.

Therefore, the configuration module does now list all registered reactions
with their type identifier and corresponding configuration.

Impact
======

It's now possible for site administrators to get an overview of all registered
reactions in the configuration module.

.. index:: Backend, ext:reactions
