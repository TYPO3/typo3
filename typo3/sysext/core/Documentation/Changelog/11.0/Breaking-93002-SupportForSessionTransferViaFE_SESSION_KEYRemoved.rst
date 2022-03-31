.. include:: /Includes.rst.txt

==========================================================================
Breaking: #93002 - Support for session transfer via FE_SESSION_KEY removed
==========================================================================

See :issue:`93002`

Description
===========

TYPO3s Frontend Session Handling has had a custom feature by setting a custom
GET variable called :html:`FE_SESSION_KEY` to inject an existing session into a
Frontend Request without having a cookie sent as response.

This seldom used feature, which was limited to Frontend sessions only, and
required knowledge of third-party integrations for TYPO3s encryption key to
create such a session key is removed.

Features for integrating sessions should instead be built with custom
AuthenticationServices, e.g. for Single-Sign-On functionality.


Impact
======

Calling TYPO3s Frontend with :html:`FE_SESSION_KEY` as GET parameter has no effect
anymore and will not pick up an existing session.


Affected Installations
======================

TYPO3 installations using this :html:`FE_SESSION_KEY` which is very rare and unlikely
to be used.


Migration
=========

Build a custom Authentication Service to log in and use user session instead
in a third-party extension.

.. index:: Frontend, NotScanned, ext:frontend
