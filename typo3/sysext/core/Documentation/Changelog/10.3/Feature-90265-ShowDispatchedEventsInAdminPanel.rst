.. include:: /Includes.rst.txt

=======================================================
Feature: #90265 - Show dispatched Events in Admin Panel
=======================================================

See :issue:`90265`

Description
===========

To promote the new PSR-14 Events and to make it easier for people to see which
kinds of events may be used, the admin panel displays all events that are
dispatched in the current request with their parameters.


Impact
======

The Admin Panel has a new section called "Events" (in "Debug") which shows all
events with their respective values that have been dispatched during the current
request. To allow smooth navigating of these objects, the symfony var-dumper
component is used.

.. index:: PHP-API, ext:adminpanel
