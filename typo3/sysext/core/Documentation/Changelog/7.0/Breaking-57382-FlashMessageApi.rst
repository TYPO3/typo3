
.. include:: ../../Includes.txt

===============================================
Breaking: #57382 - Remove old flash message API
===============================================

See :issue:`57382`

Description
===========

The old flash message API is removed.

Impact
======

Extensions relying on the old (static) flash message queue API will not work anymore.
Extbase removes the protected old flashMessageContainer.

Affected installations
======================

Any installation that uses an extension relying on the old API.

Migration
=========

Change the API calls to not be of static kind anymore.
Extbase extensions have to use `getFlashMessageQueue()` of the controllerContext


.. index:: PHP-API
