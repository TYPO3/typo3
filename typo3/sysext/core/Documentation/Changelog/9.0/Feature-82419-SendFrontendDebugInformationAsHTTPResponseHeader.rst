.. include:: /Includes.rst.txt

=========================================================================
Feature: #82419 - Send Frontend Debug Information as HTTP Response Header
=========================================================================

See :issue:`82419`

Description
===========

When setting :typoscript:`config.debug=1` or :php:`$TYPO3_CONF_VARS[FE][debug]` the parse time is now sent as HTTP
response header "X-TYPO3-Parsetime" instead of HTML comments.


Impact
======

This ensures that non-HTML-content (e.g. JSON output) does not break when having debugging for the
Frontend enabled.

If you look for the parse time of a frontend request, this can now easily be shown via
`curl -I https://mydomain.com` or in the developer toolbar of the browser.

.. index:: Frontend, TypoScript
