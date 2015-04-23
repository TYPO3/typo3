============================================
Deprecation: #64134 - Deprecate $BE_USER->OS
============================================

Description
===========

The public property in the global object ``$BE_USER->OS`` has been marked as deprecated.


Affected installations
======================

Instances with extensions that make use of the public property directly.


Migration
=========

Use the constant ``TYPO3_OS`` directly.
