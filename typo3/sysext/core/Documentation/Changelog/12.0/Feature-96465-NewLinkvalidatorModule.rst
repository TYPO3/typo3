.. include:: /Includes.rst.txt

.. _feature-96465:

==========================================
Feature: #96465 - New Linkvalidator module
==========================================

See :issue:`96465`

Description
===========

Checking a TYPO3 installation for broken links is a common and necessary
task for editors. Therefore, TYPO3 provides the "linkvalidator" system
extension, which allows to check and report broken links via the TYPO3
backend or via email. Previously, the backend part was built into the
:guilabel:`Web > Info` module.

To make "linkvalidator" more prominent, its functionality is now available
in an independent backend module :guilabel:`Web > Check links` . This also
allows administrators to define access permissions via the module access logic.

The new module still contains the two known functions "report" and
"check links". However, those are no longer divided by tabs, but as
all other modules, by different actions, which can be selected using
the corresponding dropdown in the docheader.

Impact
======

The linkvalidator reports are now available in an independent backend module.

.. index:: Backend, ext:linkvalidator
