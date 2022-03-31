
.. include:: /Includes.rst.txt

=====================================================
Feature: #50780 - Append element browser mount points
=====================================================

See :issue:`50780`

Description
===========

A new UserTSconfig option is introduced:

::

	options.pageTree.altElementBrowserMountPoints.append

This option allows administrators to add additional mount points
in the RTE and the Wizard element browser instead of replacing
the configured database mount points of the user when using the
existing UserTSconfig option:

::

	options.pageTree.altElementBrowserMountPoints

Usage:

Set these options in User TSconfig:

::

	options.pageTree.altElementBrowserMountPoints = 20,31
	options.pageTree.altElementBrowserMountPoints.append = 1


Impact
======

Mount point overriding is centralized in the BackendUser object and
used by element browsers of rtehtmlarea and recordlist for calculating
the page tree mount points that are displayed to the user.


.. index:: TSConfig, Backend
