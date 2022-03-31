.. include:: /Includes.rst.txt

======================================================
Important: #80450 - MonitorUtilityMovedToCompatibility
======================================================

See :issue:`80450`

Description
===========

The "peak memory measurement" in the frontend has been moved to extension compatiblity7. The functionality
is semi useful and should live a happy life in an extension for people who may need it, but there is no need
to have that within the core on each frontend call.

.. index:: Backend, Frontend, PHP-API
