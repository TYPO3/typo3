.. include:: /Includes.rst.txt

.. _feature-99499-1677703100:

============================================================
Feature: #99499 - Introduce Content-Security-Policy handling
============================================================

See :issue:`99499`

Description
===========

A corresponding representation of the W3C standard of
`Content-Security-Policy (CSP) <https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy>`__
has been introduced to TYPO3. Content-Security-Policy declarations can either be provided by using the
the general builder pattern of :php:`\TYPO3\CMS\Core\Security\ContentSecurityPolicy\Policy`, extension-specific
mutations (changes to the general policy) via :file:`Configuration/ContentSecurityPolicies.php`
located in corresponding extension directories, or YAML path :yaml:`contentSecurityPolicies.mutations` for
site-specific declarations in the website frontend.

The PSR-15 middlewares :php:`ContentSecurityPolicyHeaders` apply `Content-Security-Policy` HTTP headers
to each response in the frontend and backend scope. In the case that other components have already added either the
header `Content-Security-Policy` or `Content-Security-Policy-Report-Only`, those existing headers will be
kept without any modification - these events will be logged with an `info` severity.

To delegate CSP handling to TYPO3, the scope-specific feature flags need to be enabled:

* :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.backend.enforceContentSecurityPolicy']`
* :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['features']['security.frontend.enforceContentSecurityPolicy']`

For new installations `security.backend.enforceContentSecurityPolicy` is enabled via factory default settings.

Potential CSP violations are reported back to the TYPO3 system and persisted internally in the database table
:sql:`sys_http_report`. A corresponding Content-Security-Policy backend module supports users to keep track of
recent violations and - if applicable - to select potential resolutions (stored in database table
:sql:`sys_csp_resolution`) which extends the Content-Security-Policy for the given scope during runtime.

As an alternative, the reporting URL can be configured to use third-party services as well:

..  code-block:: php

    $GLOBALS['TYPO3_CONF_VARS']['BE']['contentSecurityPolicyReportingUrl']
        = 'https://csp-violation.example.org/';

    $GLOBALS['TYPO3_CONF_VARS']['FE']['contentSecurityPolicyReportingUrl']
        = 'https://csp-violation.example.org/';

Impact
======

Introducing CSP to TYPO3 aims to reduce the risk of being affected by Cross-Site-Scripting
due to the lack of proper encoding of user-submitted content in corresponding outputs.

Configuration
=============

`Policy` builder approach
-------------------------

..  code-block:: php

    <?php
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Policy;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
    use TYPO3\CMS\Core\Security\Nonce;

    $nonce = Nonce::create();
    $policy = (new Policy())
        // results in `default-src 'self'`
        ->default(SourceKeyword::self)
        // extends the ancestor directive ('default-src'), thus reuses 'self' and adds additional sources
        // results in `img-src 'self' data: https://*.typo3.org`
        ->extend(Directive::ImgSrc, SourceScheme::data, new UriValue('https://*.typo3.org'))
        // extends the ancestor directive ('default-src'), thus reuses 'self' and adds additional sources
        // results in `script-src 'self' 'nonce-[random]'` ('nonce-proxy' is substituted when compiling the policy)
        ->extend(Directive::ScriptSrc, SourceKeyword::nonceProxy)
        // sets (overrides) the directive, thus ignores 'self' of the 'default-src' directive
        // results in `worker-src blob:`
        ->set(Directive::WorkerSrc, SourceScheme::blob);
    header('Content-Security-Policy: ' . $policy->compile($nonce));

The result of the compiled and serialized result as HTTP header would look similar to this
(the following sections are using the same example, but utilize different techniques for the declarations).

..  code-block:: text

    Content-Security-Policy: default-src 'self';
        img-src 'self' data: https://*.typo3.org; script-src 'self' 'nonce-[random]';
        worker-src blob:

Extension-specific
------------------

A file :file:`Configuration/ContentSecurityPolicies.php` in the base directory
of any extension will automatically provide and apply corresponding settings.

..  code-block:: php
    :caption: EXT:my_extension/Configuration/ContentSecurityPolicies.php

    <?php
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceKeyword;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
    use TYPO3\CMS\Core\Type\Map;

    return Map::fromEntries([
        // provide declarations for the backend
        Scope::backend(),
        // NOTICE: When using `MutationMode::Set` existing declarations will be overridden
        new MutationCollection(
            // results in `default-src 'self'`
            new Mutation(MutationMode::Set, Directive::DefaultSrc, SourceKeyword::self),
            // extends the ancestor directive ('default-src'), thus reuses 'self' and adds additional sources
            // results in `img-src 'self' data: https://*.typo3.org`
            new Mutation(MutationMode::Extend, Directive::ImgSrc, SourceScheme::data, new UriValue('https://*.typo3.org')),
            // extends the ancestor directive ('default-src'), thus reuses 'self' and adds additional sources
            // results in `script-src 'self' 'nonce-[random]'` ('nonce-proxy' is substituted when compiling the policy)
            new Mutation(MutationMode::Extend, Directive::ScriptSrc, SourceKeyword::nonceProxy),
            // sets (overrides) the directive, thus ignores 'self' of the 'default-src' directive
            // results in `worker-src blob:`
            new Mutation(MutationMode::Set, Directive::WorkerSrc, SourceScheme::blob),
        ),
    ]);

Site-specific (frontend)
------------------------

In the frontend, the dedicated :file:`sites/<my-site>/csp.yaml` can be used to declare CSP for a specific site as well.

..  code-block:: yaml
    :caption: config/sites/<my-site>/csp.yaml

    # inherits default site-unspecific frontend policy mutations (enabled per default)
    inheritDefault: true
    mutations:
      # results in `default-src 'self'`
      - mode: set
        directive: 'default-src'
        sources:
          - "'self'"
      # extends the ancestor directive ('default-src'), thus reuses 'self' and adds additional sources
      # results in `img-src 'self' data: https://*.typo3.org`
      - mode: extend
        directive: 'img-src'
        sources:
          - 'data:'
          - 'https://*.typo3.org'
      # extends the ancestor directive ('default-src'), thus reuses 'self' and adds additional sources
      # results in `script-src 'self' 'nonce-[random]'` ('nonce-proxy' is substituted when compiling the policy)
      - mode: extend
        directive: 'script-src'
        sources:
          - "'nonce-proxy'"
      # results in `worker-src blob:`
      - mode: set
        directive: 'worker-src'
        sources:
          - 'blob:'

PSR-14 events
=============

PolicyMutatedEvent
------------------

The :php:`\TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\PolicyMutatedEvent` will
be dispatched once all mutations have been applied to the current policy object, just
before the corresponding HTTP header is added to the HTTP response object.
This allows individual changes for custom implementations.

InvestigateMutationsEvent
-------------------------

The :php:`\TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\InvestigateMutationsEvent` will
be dispatched when the Content-Security-Policy backend module searches for potential resolutions
to a specific CSP violation report. This way, third-party integrations that rely on external resources
(for example, maps, file storage, content processing/translation, ...) can provide the necessary mutations.

.. index:: Backend, Fluid, Frontend, LocalConfiguration, PHP-API, ext:core
