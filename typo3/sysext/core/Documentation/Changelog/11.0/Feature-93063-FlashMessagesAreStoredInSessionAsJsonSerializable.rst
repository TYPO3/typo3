.. include:: /Includes.rst.txt

=========================================================================
Feature: #93063 - FlashMessages are stored in session as JsonSerializable
=========================================================================

See :issue:`93063`

Description
===========

FlashMessages which are used to show information across backend
modules and frontend plugins / forms, are mostly stored in the
session data of a user session.

They are now stored as json_encoded data, using the already existing
:php:`JsonSerializable` functionality of AbstractMessage.


Impact
======

This way, the FlashMessage objects are only built when they are
needed and not on every PHP call the user session is started,
making e.g. AJAX calls a tiny bit faster.

.. index:: PHP-API, ext:core
