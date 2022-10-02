.. include:: /Includes.rst.txt

.. _breaking-96889:

================================================
Breaking: #96889 - Require PHP mbstring and intl
================================================

See :issue:`96889`

Description
===========

The two PHP extensions :php:`mbstring` and :php:`intl` are required to
be loaded when running TYPO3 v12.

:php:`mbstring` is a common extension that is either compiled directly
into PHP or available as standard package in all distributions and
operating systems. Similar for :php:`intl`. While there are symfony
packages that mimic these extensions partially if not available, these
"polyfill" packages are slower, and most importantly, they implement only
parts of the native extensions. To further improve TYPO3 character set
and internationalization handling, the system needs the full functionality.

Impact
======

System environments not providing these PHP extensions may fail.

Affected Installations
======================

The install tool "Environment Status" and the reports module notify
about missing PHP extensions, and it is shown during the installation process.

Migration
=========

Provide the extensions in the PHP.

A debian / ubuntu based Linux host typically install such packages with
a command similar to this:

..  code-block:: bash

    sudo apt install php8.1-mbstring php8.1-intl

.. index:: PHP-API, NotScanned, ext:core
