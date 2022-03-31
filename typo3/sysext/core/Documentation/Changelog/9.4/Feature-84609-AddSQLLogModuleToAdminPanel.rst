.. include:: /Includes.rst.txt

==================================================
Feature: #84609 - Add SQL Log Module to AdminPanel
==================================================

See :issue:`84609`

Description
===========

A new AdminPanel module has been introduced which shows SQL queries done to generate the current page.
The module includes a short trace of the query so developers may locate where it was initiated as well as - in case
it's a prepared statement - the placeholder values to enable checking variations of the query.

Logging of queries is done via the Doctrine SQL Logger capabilities and enabled whenever the AdminPanel is activated /
open in the frontend. Logging is only done for the currently logged in backend user, this way the overall performance
impact is negligible.


Impact
======

The AdminPanel has a new sub module "Query Information" in the "Debug" section.

.. index:: Backend, Database, Frontend, ext:adminpanel
