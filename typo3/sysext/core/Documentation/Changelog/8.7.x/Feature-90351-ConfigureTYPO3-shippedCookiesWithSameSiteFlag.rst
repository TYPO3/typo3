.. include:: /Includes.rst.txt

====================================================================
Feature: #90351 - Configure TYPO3-shipped cookies with SameSite flag
====================================================================

See :issue:`90351`

Description
===========

TYPO3 Core sends four cookies set by PHP to the browser when a session is requested:

- fe_typo_user - used to identify a session ID when logged-in to the TYPO3 Frontend
- be_typo_user - used to identify a backend session when a Backend User logged in to TYPO3 Backend or Frontend
- Typo3InstallTool - used to validate a session for the System Maintenance Area / "Install Tool"
- be_lastLoginProvider - stores information about the last login provider when logging into TYPO3 Backend

All modern wide-spread browsers (Mozilla Firefox, Chromium-based Browsers such as Google Chrome, Safari, Microsoft Edge) support sending cookies with an additional flag called "SameSite" which
defines the visibility of a cookie when used in other scripts or
iframes such as a YouTube video embedded into a site. The same site
flag defines whether to send such information to these "third-party
sites".

Starting with Google Chrome 80 (expected in February 2020), the browser treats any cookie without having the SameSite flag sent to
be the same as "lax".

TYPO3 now supports the configuration of this cookie for Frontend-
and Backend users. For the install Tool and lastLoginProvider
the cookies are now always sent with the "strict" flag set.

SameSite enhances privacy for every visitor or editor of your
TYPO3 installation.

Read more about SameSite cookies on: https://web.dev/samesite-cookies-explained/


Impact
======

All cookies sent by TYPO3 Core now send the SameSite flag by default, whereas TYPO3 Frontend sends the SameSite flag "lax",
and all other cookies are sent via "strict".

The cookies for Frontend User Sessions can be configured via
`$GLOBALS[TYPO3_CONF_VARS][FE][cookieSameSite]` to be either
"strict", "lax" or "none".

The cookies for Backend User Sessions can be configured via
`$GLOBALS[TYPO3_CONF_VARS][BE][cookieSameSite]` to be either
"strict", "lax" or "none".

Please note that "none" only works when running the site via HTTPS.

Older browsers without SameSite support do not consider evaluating
the SameSite flag will behave as before.

Both settings can be configured in the Install Tool / Maintenance
Area Settings module.

.. index:: LocalConfiguration, ext:core
