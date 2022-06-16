.. include:: /Includes.rst.txt

.. _important-94951-1655368665:

===================================================================
Important: #94951 - Restrict export functionality to allowed users
===================================================================

See :issue:`94951`

.. important::
    This change was introduced as part of the
    `TYPO3 11.5.11 and 10.4.29 security release <https://typo3.org/security/advisory/typo3-core-sa-2022-001>`__.

Description
===========

The export functionality has the following security drawbacks:

*   Export for editors is not limited on field level
*   The :guilabel:`Save to filename` functionality saves to a shared folder,
    which other editors with different access rights may have access to.

Both issues are not easy to resolve and also the target
audience for the Import/Export functionality are mainly
TYPO3 admins.

Impact
======

The export functionality is restricted
to TYPO3 admin users and to users, who explicitly have
access through the new user TSConfig setting
:typoscript:`options.impexp.enableExportForNonAdminUser`.

Affected installations
======================

Installations with EXT:impexp installed where non-admin users need to use the
export functionality.

Migration
=========

If non-admin users should be able to use the export tool, set the
following user TSconfig:

.. code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/TSconfig/allusers.tsconfig

    options.impexp.enableExportForNonAdminUser = 1

.. index:: Backend, TSConfig, NotScanned, ext:impexp
