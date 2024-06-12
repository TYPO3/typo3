.. include:: /Includes.rst.txt

.. _feature-104069-1718551315:

======================================================================
Feature: #104069 - Improved backend notifications display and handling
======================================================================

See :issue:`104069`

Description
===========

The notifications shown on the lower right now have a "Clear all" button to allow the
user to clear all notifications with a single click. This button is only displayed when
two or more notifications are on screen.

In case the height of the notification container exceeds the viewport, a scroll bar will
allow the user to navigate through the notifications.

Impact
======

Handling of multiple notifications has been improved by allowing to
scroll and clear all notifications at once.

.. index:: Backend, ext:backend
