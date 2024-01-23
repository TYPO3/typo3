.. include:: /Includes.rst.txt

.. _feature-99485-1673115922:

====================================================
Feature: #99485 - Show the redirect integrity status
====================================================

See :issue:`99485`

Description
===========

The integrity check command checks redirects and displays the information in the
CLI or in the status report. This status information is now used and stored on
the redirect.

The command :bash:`redirects:cleanup` has been extended by the option :bash:`integrityStatus`.
This allows you to remove specific redirects according to status.

The event :php:`\TYPO3\CMS\Redirects\Event\ModifyRedirectManagementControllerViewDataEvent`
has been extended by two new functions:

-   :php:`setIntegrityStatusCodes()`: Allows to set integrityStatusCodes. Can be
    used to filter for integrityStatusCodes
-   :php:`getIntegrityStatusCodes()`: Returns all integrityStatusCodes.

Impact
======

In the redirect module, the conflicting redirects are now marked. In addition,
you can now filter for conflicting redirects.

In the :bash:`redirects:checkintegrity` command, the type of conflict is now displayed
in the table.

.. index:: Backend, ext:redirects
