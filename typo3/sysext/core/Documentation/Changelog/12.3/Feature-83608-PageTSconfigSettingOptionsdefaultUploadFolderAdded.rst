.. include:: /Includes.rst.txt

.. _feature-83608-1668162306:

===========================================================================
Feature: #83608 - Page TSconfig setting "options.defaultUploadFolder" added
===========================================================================

See :issue:`83608`

Description
===========

A new page TSconfig option :typoscript:`options.defaultUploadFolder` is added.


Impact
======

Identical to the user TSconfig setting :typoscript:`options.defaultUploadFolder`,
this allows default upload folder per page to be set.

If specified and the given folder exists, this setting will override the value
defined in user TSconfig.

Example
-------

..  code-block:: typoscript

    # Set default upload folder to "fileadmin/page_upload" on PID 1
    [page["uid"] == 1]
        options.defaultUploadFolder = 1:/page_upload/
    [end]

.. index:: TSConfig, ext:core
