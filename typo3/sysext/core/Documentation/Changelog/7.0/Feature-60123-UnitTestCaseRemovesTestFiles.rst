
.. include:: ../../Includes.txt

========================================================
Feature: #60123 - Unit base test case removes test files
========================================================

See :issue:`60123`

Description
===========

Some unit tests need to create test files or directories to check the system
under test. Those files should be removed again.
A test can now register absolute file paths in :code:`$this->testFilesToDelete`, and
the generic :code:`tearDown()` method will then remove them. Only files, links and directories
within typo3temp/ are allowed.

Impact
======

This allows tests to clean up the environment without leaving obsolete test files behind.
