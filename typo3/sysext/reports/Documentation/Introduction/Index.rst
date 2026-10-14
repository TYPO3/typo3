.. include:: /Includes.rst.txt

..  _introduction:

============
Introduction
============

..  contents:: Table of contents

..  _what-does-it-do:

What does it do?
================

.. figure:: /Images/ModuleReports.png
   :class: with-shadow

   The backend module :guilabel:`Administration > Reports`

The TYPO3 system extension EXT:reports displays the extendable backend module
:guilabel:`Administration > Reports` for users with administrator role.

The Reports module groups several system reports and gives you a quick
overview about important system statuses and site parameters.

..  _check-security:

Section "Security"
==================

.. figure:: /Images/Security.png
   :class: with-shadow

   Regularly check the section :guilabel:`Security`

From a security perspective, the section :guilabel:`Security` should be checked
regularly: it provides information about the administrator user
account, encryption key, file deny pattern, module :guilabel:`System > Environment`
checks and more.

.. warning::
    In case of a compromised system, the information displayed here may
    have been manipulated by the attacker.

    Thus, if no problems are pointed out - it does not necessarily mean
    that there are no security issues. But, on the other hand, if security
    problems are pointed out, you most certainly should fix them.

..  _record-statistics:

Record Statistics
=================

..  versionchanged:: 14.0
    The record statistics have been moved from module :guilabel:`System > Database`,
    provided by the system extension :composer:`typo3/cms-lowlevel` into the
    module :guilabel:`Administration > Reports` of this extension :composer:`typo3/cms-reports`.

Backend users with administrator permissions can find this module at
:guilabel:`Administration > Reports > Records Statistics`.

This module gives an overview of the count of records of different types.

..  figure:: /Images/RecordStatistics.png
    :alt: The TYPO3 backend module "Reports" with submodule "Record Statistics"

The records are counted installation-wide. Soft-deleted (flag `deleted = 1`)
records are ignored.

..  _content-statistics:

Content statistics
==================

..  versionadded:: 14.2
    :changelog: feature-107940-1761853022

Backend users with administrator permissions can find this module at
:guilabel:`Administration > Reports > Content Statistics`.

The module lists all content element types and how often each type is used.
The counts include visible and hidden records. Records that are flagged as
deleted are not counted.

..  figure:: /Images/ContentStatistics.avif
    :alt: The module "Content Statistics" with the content element types of
        the group "Typical page content" and the number of their records

    The content element types and how often they are used

The list is grouped by the groups of the content element types. The section
**Unused content elements** lists the types that no record uses, or whose
records are all deleted. For a type with deleted records, the number of
deleted records is shown. Use this section to find content element types that
you can remove from the configuration.

A type that has records is not necessarily shown on a page. A record can be
stored on a page that is not rendered, and content can also be rendered by
TypoScript or Fluid. The module cannot detect such usage.

..  _content-statistics-fields:

Fields of a content element type
--------------------------------

Expand a content element type to see its fields. For each field the list
shows:

*   The field type, for example `input` or `select`
*   **Always visible**: the field is not an exclude field, so backend user
    groups cannot be denied access to it
*   **Required**: the field must have a value

..  figure:: /Images/ContentStatisticsFields.avif
    :alt: The expanded content element type "Regular Text Element" with its
        fields and the columns Name, Type, Always visible, and Required

    The fields of a content element type

..  _content-statistics-details:

Records of a content element type
---------------------------------

Click :guilabel:`Details` to open the detail view of a content element type.
It shows the number of enabled, disabled, and deleted records, and lists the
records that are not deleted with their page and path. Click the edit button
of a record to edit it.

..  figure:: /Images/ContentStatisticsDetail.avif
    :alt: The detail view of the content element type "Regular Text Element"

    The detail view of a content element type
