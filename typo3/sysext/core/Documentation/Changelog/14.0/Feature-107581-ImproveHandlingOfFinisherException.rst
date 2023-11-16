.. include:: /Includes.rst.txt

.. _feature-107581-1759418765:

========================================================
Feature: #107581 - Improve handling of FinisherException
========================================================

See :issue:`107581`

Description
===========

FinisherExceptions thrown during form processing are now caught within the respective finisher.
Instead of resulting in a generic 503 error page, the exception is logged and
a user-friendly error message is displayed to the user, indicating that the form could not be submitted successfully.
The error message can be customized for each finisher using the new :yaml:`errorMessage` option.
Additionally, the newly introduced "Error" template can be overridden and customized.

Impact
======

Users no longer see a 503 error page if a FinisherException occurs;
instead, they receive a clear, user-friendly message in the form frontend.
All FinisherExceptions are logged for further analysis and debugging.
This improves the user experience and makes error handling in forms more flexible and transparent.

.. index:: Frontend, UX, ext:form
