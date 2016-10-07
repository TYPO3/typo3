
.. include:: ../../Includes.txt

=============================================================
Deprecation: #73068 - Deprecated "default" argument on f:case
=============================================================

See :issue:`73068`

Description
===========

Due to the switch to Fluid standalone, the following template markup is not supported anymore:

.. code-block:: html

   <f:case default="true"> ... </f:case>

It must be changed to read:

.. code-block:: html

   <f:defaultCase> ... </f:defaultCase>

A compatibility layer is in place which logs the deprecation.


Impact
======

Use of "default" argument on f:case will trigger a deprecation log entry.


Affected Installations
======================

Any TYPO3 site which uses f:case with argument "default" regardless of the argument value.


Migration
=========

Switch `f:case` to `f:defaultCase` for the case node that is your default case.

.. index:: Fluid
