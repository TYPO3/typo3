.. include:: /Includes.rst.txt

.. _introduction:

============
Introduction
============

What does it do?
================

.. figure:: /Images/ModuleReports.png
   :class: with-shadow

   The backend module :guilabel:`System > Reports`

The TYPO3 system extension EXT:reports displays the extendable backend module
:guilabel:`System > Reports` for users with administrator role.

The Reports module groups several system reports and gives you a quick
overview about important system statuses and site parameters.

Section "Security"
==================

.. figure:: /Images/Security.png
   :class: with-shadow

   Regularily check the section :guilabel:`Security`

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
