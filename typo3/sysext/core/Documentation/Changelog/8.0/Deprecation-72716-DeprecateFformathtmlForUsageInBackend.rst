==================================================================
Deprecation: #72716 - Deprecate f:format.html for usage in backend
==================================================================

Description
===========

The usage of the viewhelper ``f:format.html`` in the backend context is discouraged
because of possible side effects - e.g. no ``lib.parseFunc`` is configured.


Impact
======

Using the viewhelper ``f:format.html`` in the backend context will trigger a
deprecation log entry.


Affected Installations
======================

Any TYPO3 instance using the viewhelper in the backend context.


Migration
=========

Use the viewhelper ``f:format.raw``.

.. index:: fluid
