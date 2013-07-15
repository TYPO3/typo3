.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../../Includes.txt



.. _oracle-to-do:

Oracle
^^^^^^

- Insert a string longer than the size of the field in the database
  (MySQL just silently accepts this...) - so will we have to evaluate
  all values in update/insert queries first?

- Does not allow us to CHANGE existing fields into something else - only
  create new fields, otherwise we must export/import database.

- A quoted value cannot be inserted into an integer field!!!

