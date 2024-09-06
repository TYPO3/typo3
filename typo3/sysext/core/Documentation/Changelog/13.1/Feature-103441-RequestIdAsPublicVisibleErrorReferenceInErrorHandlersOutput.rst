.. include:: /Includes.rst.txt

.. _feature-103441-1710969809:

========================================================================================
Feature: #103441 - Request ID as public visible error reference in error handlers output
========================================================================================

See :issue:`103441`

Description
===========

The :php:`ProductionExceptionHandler` in EXT:core outputs error details, but not
for everyone. As a normal visitor you don't see any traceable error information.

The :php:`ProductionExceptionHandler` in EXT:frontend outputs "Oops, an error
occurred!" followed by a timestamp and a hash. This is part of log messages.

Whenever an error/exception is logged, the log message contains the request ID.

With this the request ID is also shown in web output of error/exception handlers
as public visible error reference.


Impact
======

Everyone sees a request id as traceable error information.

.. index:: Frontend, ext:core
