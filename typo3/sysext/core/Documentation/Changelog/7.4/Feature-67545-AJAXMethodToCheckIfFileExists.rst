
.. include:: ../../Includes.txt

========================================================
Feature: #67545 - AJAX call to check whether file exists
========================================================

See :issue:`67545`

Description
===========

A Backend AJAX call to check whether a file exists has been added. The call needs two parameters to work properly.


Impact
======

The method can be called with `TYPO3.settings.ajaxUrls['file_exists']`.
The parameters `fileName` and `fileTarget` are required:

* fileName: Name of the file
* fileTarget: Combined identifier of target directory for the file
