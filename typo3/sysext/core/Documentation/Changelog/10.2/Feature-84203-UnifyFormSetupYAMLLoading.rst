.. include:: /Includes.rst.txt

===============================================
Feature: #84203 - Unify form setup YAML loading
===============================================

See :issue:`84203`

Description
===========

Form setup files of the "form" extension now make use of the TYPO3 core YAML file loader. This allows
using the known TYPO3 core features:

* import of other YAML files via :yaml:`imports` directive
* replacement of :yaml:`%placeholders%`


Impact
======

Form setups can now be structured more freely by splitting logical parts into separate files.

.. index:: Backend, Frontend, ext:form
