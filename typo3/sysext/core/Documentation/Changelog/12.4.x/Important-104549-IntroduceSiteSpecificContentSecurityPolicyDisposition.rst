.. include:: /Includes.rst.txt

.. _important-104549-1723461851:

================================================================================
Important: #104549 - Introduce site-specific Content-Security-Policy-Disposition
================================================================================

See :issue:`104549`

Description
===========

The feature flags :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.frontend.enforceContentSecurityPolicy']`
and :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.frontend.reportContentSecurityPolicy']` apply
Content-Security-Policy headers to any frontend site. The dedicated :file:`sites/<my-site>/csp.yaml` can now be
used as alternative to declare the desired disposition of `Content-Security-Policy` and
`Content-Security-Policy-Report-Only` individually.

It now is also possible, to apply both `Content-Security-Policy` and `Content-Security-Policy-Report-Only`
HTTP headers at the same time with different directives for a particular site. Besides that it is possible
to disable the disposition completely for a site.

The following new configuration schemes were introduced for :file:`sites/<my-site>/csp.yaml`:

* `active (false)` for disabling CSP for a particular site, which overrules any other setting for `enforce` or `report`
* `enforce (bool|disposition-array)` for compiling the `Content-Security-Policy` HTTP header
* `report (bool|disposition-array)` for compiling the `Content-Security-Policy-Report-Only` HTTP header

The `disposition-array` for `enforce` and `report` allows these properties:

* `inheritDefault (bool)` inherits default site-unspecific frontend policy mutations (`true` per default)
* `includeResolutions (bool)` includes dynamic resolutions, as persisted in the database via backend module (`true` per default)
* `mutations (mutation-item-array)` defines additional directive mutations to be applied to the specific site
* `packages (package-item-array)` defines packages/extensions whose static CSP mutations shall be dropped or included

Example: Disable Content-Security-Policy
----------------------------------------

The following example would completely disable CSP for a particular site.

..  code-block:: yaml
    :caption: config/sites/<my-site>/csp.yaml

    # `active` is enabled per default if omitted
    active: false

Example: Use `report` disposition
---------------------------------

The following example would dispose only `Content-Security-Policy-Report-Only`
for a particular site (since the `enforce` property is not given).

..  code-block:: yaml
    :caption: config/sites/<my-site>/csp.yaml

    report:
      # `inheritDefault` is enabled per default if omitted
      inheritDefault: true
      mutations:
       - mode: extend
         directive: img-src
         sources:
          - https://*.typo3.org

The following example is equivalent to the previous, but shows that the
legacy configuration (having `inheritDefault` and `mutations` on the top-level)
is still supported.

The effective HTTP headers would then be resolved from the active feature flags
`security.frontend.enforceContentSecurityPolicy` and
`security.frontend.reportContentSecurityPolicy` - in case both flags are active,
both HTTP headers `Content-Security-Policy` and `Content-Security-Policy-Read-Only`
would be used.

..  code-block:: yaml
    :caption: config/sites/<my-site>/csp.yaml

    # `inheritDefault` is enabled per default if omitted
    inheritDefault: true
    mutations:
     - mode: extend
       directive: img-src
       sources:
        - https://*.typo3.org

Example: Use `enforce` and `report` dispositions at the same time
-----------------------------------------------------------------

The following example would dispose `Content-Security-Policy` (`enforce`)
and `Content-Security-Policy-Report-Only` (`report`) for a particular site.

This allows to test new CSP directives in the frontend - the example drops
the static CSP directives of the package `my-vendor/my-package` in the
enforced disposition and only applies it to the reporting disposition.

..  code-block:: yaml
    :caption: config/sites/<my-site>/csp.yaml

    enforce:
      # `inheritDefault` is enabled per default if omitted
      inheritDefault: true
      # `includeResolutions` is enabled per default if omitted
      includeResolutions: true
      mutations:
        - mode: extend
          directive: img-src
          sources:
            - https://*.typo3.org
      packages:
        # all (`*`) packages shall be included (`true`)
        '*': true
        # the package `my-vendor/my-package` shall be dropped (`false`)
        my-vendor/my-package: false

    report:
      # `inheritDefault` is enabled per default if omitted
      inheritDefault: true
      # `includeResolutions` is enabled per default if omitted
      includeResolutions: true
      mutations:
        - mode: extend
          directive: img-src
          sources:
            - https://*.my-vendor.example.org/
      # the `packages` section can be omitted in this case, since all packages
      # listed there shall be included - which is the default behavior in case
      # `packages` would not be configured
      packages:
        # all (`*`) packages shall be included (`true`)
        '*': true
        # the package `my-vendor/my-package` shall be included (`true`)
        my-vendor/my-package: true

.. index:: Frontend, YAML, ext:frontend
