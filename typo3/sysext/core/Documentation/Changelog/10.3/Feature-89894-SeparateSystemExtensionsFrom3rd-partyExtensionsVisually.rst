.. include:: /Includes.rst.txt

===============================================================================
Feature: #89894 - Separate system extensions from 3rd-party extensions visually
===============================================================================

See :issue:`89894`

Description
===========

The Extension Manager in TYPO3 allows backend users to list, activate, deactivate, configure
and possibly add/remove extensions from the system. When using the Extension Manager,
backend users work with either core extensions (system extensions) or 3rd-party extensions,
depending on their task.

The extension list shown in the Extension Manager can now be filtered by certain extension types (system and 3rd-party extensions).


Impact
======

A limited list of extensions, either system or 3rd-party extensions, makes it easier for backend users
to find the extension they intend to work with and/or to get a quick overview which extensions are
currently installed (e.g. 3rd-party extensions). This improves the usability of the backend for integrators/administrators.

.. index:: Backend, ext:extensionmanager
