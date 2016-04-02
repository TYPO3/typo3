=================================================================
Deprecation: #71153 - Several DocumentTemplate methods deprecated
=================================================================

Description
===========

The following methods from ``TYPO3\CMS\Backend\Template\DocumentTemplate`` have
been marked as deprecated.

``section``
``divider``
``sectionHeader``
``sectionBegin``
``sectionEnd``

Impact
======

Using these methods will trigger a deprecation log entry.


Affected Installations
======================

Instances with custom backend modules that use one of the aforementioned methods.


Migration
=========

Use plain HTML instead.

.. index:: php
