.. include:: /Includes.rst.txt

===================================================================================
Breaking: #92807 - Removed feature for keeping session data on frontend user logout
===================================================================================

See :issue:`92807`

Description
===========

When a frontend user logged out, the session data was kept
and transferred to an anonymous session when the feature
flag :php:`security.frontend.keepSessionDataOnLogout` was enabled.

Since this functionality is insecure, and was only introduced
to keep backwards-compatibility in a security release, the feature
has been removed completely.


Impact
======

When logging out as a frontend user, all session data is now
actively removed and not kept as a new anonymous session.


Affected Installations
======================

TYPO3 installations having this feature enabled and actively
using this feature, e.g. in cart functionality.


Migration
=========

It is recommended to build the web application in a way that
the session data is not needed, and instead a frontend user
should know that their session data is lost upon log out.

Make sure to bind user-specific data either to the
frontend user itself, or re-implement this functionality
yourself by using a :php:`logoff()` hook for transferring sessions
to anonymous sessions.

.. index:: Frontend, PHP-API, NotScanned, ext:frontend
