.. include:: /Includes.rst.txt

====================================================
Feature: #85698 - New type=input eval saltedPassword
====================================================

See :issue:`85698`

Description
===========

Setting passwords and storing them as salted passwords in the database
is now supported by adding the eval option :php:`saltedPassword` to :php:`TCA` :php:`type=input`
fields.

Fields having this eval set will get their value evaluated to a salted
hash before they are stored by the :php:`DataHandler`.

Note the salt configuration for backend (BE) is considered when using this eval
on tables that is not the :php:`fe_users` table.


Impact
======

The new eval substitutes custom code that has been done within the
salted passwords extension before. It has no impact on instances
being upgraded.


.. index:: Backend, TCA
