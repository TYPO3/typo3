.. include:: /Includes.rst.txt

.. _important-101580-1723653576:

===========================================================================
Important: #101580 - Introduce Content-Security-Policy-Report-Only handling
===========================================================================

See :issue:`101580`

Description
===========

The feature flag `security.frontend.reportContentSecurityPolicy`
(:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.frontend.reportContentSecurityPolicy']`)
can be used to apply the `Content-Security-Policy-Report-Only` HTTP header for
frontend responses.

When both feature flags are activated, both headers are sent.
You can deactivate one disposition in the site-specific configuration.

This allows to test and assess the potential impact on introducing
Content-Security-Policy in the frontend - without actually blocking
any functionality.

This behavior can be controlled on a site-specific scope as well, see
:ref:`Important: #104549 - Introduce site-specific Content-Security-Policy-Disposition <important-104549-1723461851>`.

.. index:: Frontend, LocalConfiguration, ext:frontend
