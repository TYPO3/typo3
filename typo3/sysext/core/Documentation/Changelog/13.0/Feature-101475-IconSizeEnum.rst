.. include:: /Includes.rst.txt

.. _feature-101475-1690546909:

===============================
Feature: #101475 - IconSizeEnum
===============================

See :issue:`101475`

Description
===========

A new backed enum :php:`\TYPO3\CMS\Core\Imaging\IconSize` is introduced to be
used in conjunction with the Icon API.


Impact
======

The introduced enum acts as a streamlined drop-in replacement for the existing
:php:`\TYPO3\CMS\Core\Imaging\Icon::SIZE_*` string constants.

.. index:: Backend, PHP-API, ext:core
