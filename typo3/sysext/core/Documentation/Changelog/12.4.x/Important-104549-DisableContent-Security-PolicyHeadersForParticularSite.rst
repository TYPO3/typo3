.. include:: /Includes.rst.txt

.. _important-104549-1723461851:

================================================================================
Important: #104549 - Disable Content-Security-Policy headers for particular site
================================================================================

See :issue:`104549`

Description
===========

The feature flag :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.frontend.enforceContentSecurityPolicy']`
applies Content-Security-Policy headers to any frontend site. The dedicated :file:`sites/<my-site>/csp.yaml` can be used
to explicitly disable CSP for a particular site.

..  code-block:: yaml
    :caption: config/sites/<my-site>/csp.yaml

    # enables content-security-policy headers for this specific site (enabled per default)
    # (`enable: false` can be used to disable CSP for a particular site)
    enable: false

.. index:: Frontend, YAML, ext:frontend
