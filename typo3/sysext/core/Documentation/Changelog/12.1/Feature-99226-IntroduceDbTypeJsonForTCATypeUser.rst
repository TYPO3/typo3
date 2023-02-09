.. include:: /Includes.rst.txt

.. _feature-99226-1669801019:

=========================================================
Feature: #99226 - Introduce dbType json for TCA type user
=========================================================

See :issue:`99226`


.. attention::

    This TCA option is **no longer available**! It has been
    :ref:`replaced <important-100088-1677950866>` by the dedicated
    :ref:`json <feature-100088-1677965005>` TCA type. Do not use
    this option in your installation, but use the new TCA type.

Description
===========

To allow storage and usage of JSON data in TCA type `user` without needing to
decode the JSON in each user implementation manually, a dbType is introduced
for TCA type `user`.


Impact
======

When creating TCA type `user` fields with a database JSON field, the
dbType `json` can now be set. After setting the dbType, the form engine will
automatically provide the decoded JSON to the RecordProviders and the `user`
PHP implementation can then use the field value.

.. index:: Backend, PHP-API, ext:backend
