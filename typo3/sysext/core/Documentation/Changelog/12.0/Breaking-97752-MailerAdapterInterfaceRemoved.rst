.. include:: /Includes.rst.txt

.. _breaking-97752-1654761506:

=================================================
Breaking: #97752 - MailerAdapterInterface removed
=================================================

See :issue:`97752`

Description
===========

The :php:`\TYPO3\CMS\Core\Mail\MailerAdapterInterface` has been removed,
since the interface became unused in v7 due to removal of Core's
:php:`SwiftMailerAdapter` implementation, which had been used as hook
subscriber in the also removed :php:`MailUtility::mail()` method.

Impact
======

Implementing the interface in custom extension code will trigger
a PHP Error.

Affected installations
======================

All installations implementing the interface in custom extension code,
which is very unlikely. The extension scanner will report any usage as
strong match.

Migration
=========

Remove any usage of the interface in extension code.

.. index:: PHP-API, FullyScanned, ext:core
