.. include:: /Includes.rst.txt

====================================================
Feature: #88318 - Display Application Context in CLI
====================================================

See :issue:`88318`

Description
===========

The current Application Context is now shown next to the TYPO3 version number in CLI requests.
This makes it easier to check if the correct context is provided.

Output example:

.. code-block:: none

   TYPO3 CMS 10.1.0-dev (Application Context: Development/Docker)

.. index:: CLI, ext:core
