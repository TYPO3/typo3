.. include:: /Includes.rst.txt

=========================================================================
Breaking: #82803 - Global configuration option "content_doktypes" removed
=========================================================================

See :issue:`82803`

Description
===========

The configuration option :php:`$TYPO3_CONF_VARS['FE']['content_doktypes']` which declared
what pages.doktypes values could contain content elements, was removed.


Impact
======

Using this option in custom code will lead to unexpected behaviour.

Changing this option has no effect on TYPO3 Core anymore.


Affected Installations
======================

Installations having this option explicitly set.


Migration
=========

Remove all usages working with this option.

.. index:: LocalConfiguration, NotScanned
