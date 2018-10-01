.. include:: ../../Includes.txt

=========================================================
Feature: #58637 - Purge language packs in language module
=========================================================

See :issue:`58637`

Description
===========

The language module in the backend offers the possibility to activate and deactivate language packs.
If deactivating a language pack that previously had been loaded, the data stays in `<labels-path>/<locale>/`.
A remove button has been added to the actions. With the remove action the language is disabled and the data is removed
from the `<labels-path>/<locale>/` directory.


Impact
======

The language data can now be removed from the installation file system using the backend user interface.

.. index:: Backend
