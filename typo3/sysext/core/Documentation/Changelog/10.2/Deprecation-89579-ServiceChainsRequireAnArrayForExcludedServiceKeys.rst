.. include:: /Includes.rst.txt

==============================================================================
Deprecation: #89579 - ServiceChains require an array for excluded Service keys
==============================================================================

See :issue:`89579`

Description
===========

The Service API within :php:`GeneralUtility::makeInstanceService()` and
:php:`ExtensionManagementUtility::findService()` has a third argument called
:php:`$excludeServiceKeys` which is used for skipping certain services when
using a chain.

The third argument could previously be a comma-separated list
or an array. The argument now requires an array for consistency
and performance reasons.

Handing in comma-separated value strings is deprecated and will
be removed in TYPO3 v11.0.


Impact
======

Calling any of the methods above with a non-array as third argument
will trigger a deprecation notice.


Affected Installations
======================

Any TYPO3 installation with custom extensions using the Service API
directly. Extensions that ship a custom authentication provider
are not affected.


Migration
=========

Ensure to hand in an array as third argument.

.. index:: PHP-API, NotScanned, ext:core
