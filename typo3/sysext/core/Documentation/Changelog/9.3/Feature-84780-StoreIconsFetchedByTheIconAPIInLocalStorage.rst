.. include:: /Includes.rst.txt

=====================================================================
Feature: #84780 - Store icons fetched by the Icon API in localStorage
=====================================================================

See :issue:`84780`

Description
===========

Icons that get fetched by the JavaScript-based Icon API are now stored in the localStorage of the client.
A hash is calculated based on the state of the IconRegistry and stored in the localStorage as well to determine whether
the icon markup needs to get refetched from the server.

.. index:: Backend, JavaScript, ext:backend
