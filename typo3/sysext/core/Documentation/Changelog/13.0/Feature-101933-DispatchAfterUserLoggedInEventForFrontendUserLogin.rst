.. include:: /Includes.rst.txt

.. _feature-101933-1694883742:

==========================================================================
Feature: #101933 - Dispatch AfterUserLoggedInEvent for frontend user login
==========================================================================

See :issue:`101933`

Description
===========

The :php:`AfterUserLoggedInEvent` PSR-14 event, which has been introduced
with TYPO3 12.3, is now also triggered, when a frontend user has successfully
been authenticated.

..  seealso::
    :ref:`breaking-101933-1695472624`

Impact
======

It is now possible to modify and adapt user functionality based on successful
frontend user login.

.. index:: Frontend, PHP-API, ext:frontend
