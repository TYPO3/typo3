.. include:: /Includes.rst.txt

===================================================================================
Important: #89269 - Introduce Upgrade Wizard for invalid Backend User configuration
===================================================================================

See :issue:`89269`

Description
===========

Each database record in table `be_users` stores an individual user configuration in
the field `uc`. It is exposed during runtime in :php:`$GLOBALS['BE_USER']->uc`.

In older TYPO3 versions serialized class instances of :php:`\stdClass` have been
persisted to mentioned field - which is not possible anymore since TYPO3 v9.

A corresponding upgrade wizard **"Update backend user configuration array"**
aims to convert :php:`\stdClass` data to plain PHP arrays using scalar values.

Since this upgrade wizard was introduced late with TYPO3 v9.5.11 it is suggested
to make sure this upgrade step is executed in case a website has been updated
before (e.g. from TYPO3 v8 to TYPO3 v9.5.1).

.. index:: Backend, Database, ext:install
