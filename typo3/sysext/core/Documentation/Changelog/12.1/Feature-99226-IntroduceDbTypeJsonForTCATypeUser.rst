.. include:: /Includes.rst.txt

.. _feature-99226-1669801019:

=========================================================
Feature: #99226 - Introduce dbType json for TCA type user
=========================================================

See :issue:`99226`

Description
===========

To allow storage and usage of JSON data in TCA type `user` without needing to
decode the JSON in each user implementation manually, a dbType is introduced
for TCA type `user`.


Impact
======

When creating TCA type `user` fields with a database JSON field, the
dbType `user` can now be set. After setting the dbType, the form engine will
automatically provide the decoded JSON to the RecordProviders and the `user`
PHP implementation can then use the field value.

.. index:: Backend, PHP-API, ext:backend
