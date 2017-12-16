
.. include:: ../../Includes.txt

==========================================================
Feature: #73461 - Enable import module for non admin users
==========================================================

See :issue:`73461`

Description
===========

The new userTsConfig option :ts:`options.impexp.enableImportForNonAdminUser` can be used to enable
the import module of EXT:impexp for non admin users.


Impact
======

This option should be enabled for "trustworthy" backend users only.
