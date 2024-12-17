..  include:: /Includes.rst.txt

..  _important-105856-1737555887:

==========================================================================
Important: #105856 - Allow site-specific Content-Security-Policy endpoints
==========================================================================

See :issue:`105856`

Description
===========

The way Content-Security-Policy reporting endpoints are configured has
been enhanced. Administrators can now disable the reporting endpoint
globally or configure it per site as needed.

The global scope-specific setting `contentSecurityPolicyReportingUrl` can
be set to zero ('0') to disable the CSP reporting endpoint:

* :php:`[TYPO3_CONF_VARS][FE][contentSecurityPolicyReportingUrl] = '0'`
* :php:`[TYPO3_CONF_VARS][BE][contentSecurityPolicyReportingUrl] = '0'`

Additionally, the behavior of the reporting endpoint can also be
configured per site via :file:`sites/<my-site>/csp.yaml`.

The new disposition-specific property `reportingUrl` can either be:

* `reportingUrl (true)` to enable the reporting endpoint
* `reportingUrl (false)` to disable the reporting endpoint
* `reportingUrl (string)` to use the given value as external reporting endpoint

If defined, the site-specific configuration takes precedence over
the global configuration.

In case the explicitly disabled endpoint still would be called, the
server-side process responds with a 403 HTTP error message.

Example: Disabling the reporting endpoint
-----------------------------------------

..  code-block:: yaml
    :caption: config/sites/<my-site>/csp.yaml

    enforce:
      inheritDefault: true
      mutations: {}
      reportingUrl: false

Example: Using custom external reporting endpoint
-------------------------------------------------

..  code-block:: yaml
    :caption: config/sites/<my-site>/csp.yaml

    enforce:
      inheritDefault: true
      mutations: {}
      reportingUrl: https://example.org/csp-report

..  index:: Backend, Frontend, YAML, ext:backend
