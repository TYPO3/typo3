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

   The backend module :guilabel:`System > Reports`

The TYPO3 system extension EXT:reports displays the extendable backend module
:guilabel:`System > Reports` for users with administrator role.

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
account, encryption key, file deny pattern, :guilabel:`Admin Tools` checks
and more.

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
    The record statistics have been moved from module :guilabel:`System > DB Check`,
    provided by the system extension :composer:`typo3/cms-lowlevel` into the
    module :guilabel:`System > Reports` of this extension :composer:`typo3/cms-reports`.

Backend users with administrator permissions can find this module at
:guilabel:`System > Reports > Records Statistics`.

This module gives an overview of the count of records of different types.

..  figure:: /Images/RecordStatistics.png
    :alt: The TYPO3 backend module "Reports" with submodule "Record Statistics"

The records are counted installation-wide. Soft-deleted (flag `deleted = 1`)
records are ignored.
