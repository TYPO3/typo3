.. include:: /Includes.rst.txt

================================================================================
Feature: #93011 - Authentication-related cookies are attached to PSR-7 Responses
================================================================================

See :issue:`93011`

Description
===========

Cookies, used to keep the session identifiers for Frontend sessions
and Backend user sessions, were previously added natively via PHP
:php:`header()` and :php:`setcookie()` at the very beginning
of the User Authentication workflow, although this did not allow
for later-on manipulation of these HTTP response headers, as they were
emitted directly via PHP when calling the native PHP functions.

TYPO3 now attaches the cookie information for the user session information to
the PSR-7 Responses, by default in a PSR-15 middleware.


Impact
======

It is now possible to attach the cookies to a PSR-7 Response via
:php:`$GLOBALS[BE_USER]->appendCookieToResponse()`, which is especially handy
in custom middlewares that have custom endpoints when using other PHP
frameworks via the PSR-15 middleware stack.

.. index:: Backend, Frontend, ext:core
