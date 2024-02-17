.. include:: /Includes.rst.txt

.. _important-103140-1708522119:

=============================================================================================
Important: #103140 - Allow to configure rate limiters in Message consumer (Symfony Messenger)
=============================================================================================

See :issue:`103140`

Description
===========

This change introduces missing configuration options for Symfony Messenger-based
rate limiters.

A **rate limiter** controls how frequently a specific event (e.g., HTTP request
or login attempt) is allowed to occur. It acts as a safeguard to prevent services from
being overwhelmed — either accidentally or intentionally — thus helping
to maintain their availability.

Rate limiters are also useful for controlling internal or outbound
processes, such as limiting the simultaneous processing of messages.

More information about the rate limiter is available in the
`Symfony Rate Limiter component documentation
<https://symfony.com/doc/current/rate_limiter.html>`__.

Usage
=====

Configure a rate limiter per queue
----------------------------------

Rate limiters can be defined in your service configuration
:file:`EXT:yourext/Configuration/Services.yaml`. The name specified
in the settings is resolved to a service tagged with `messenger.rate_limiter`
and the corresponding identifier.

Example Configuration:

..  code-block:: yaml
    :caption: EXT:yourext/Configuration/Services.yaml
    :emphasize-lines: 10-12,23-25

    messenger.rate_limiter.demo:
      class: 'Symfony\Component\RateLimiter\RateLimiterFactory'
      arguments:
        $config:
          id: 'demo'
          policy: 'sliding_window'
          limit: '100'
          interval: '60 seconds'
        $storage: '@Symfony\Component\RateLimiter\Storage\InMemoryStorage'
      tags:
        - name: 'messenger.rate_limiter'
          identifier: 'demo'

    messenger.rate_limiter.default:
      class: 'Symfony\Component\RateLimiter\RateLimiterFactory'
      arguments:
        $config:
          id: 'default'
          policy: 'sliding_window'
          limit: '100'
          interval: '60 seconds'
        $storage: '@Symfony\Component\RateLimiter\Storage\InMemoryStorage'
      tags:
        - name: 'messenger.rate_limiter'
          identifier: 'default'

.. index:: PHP-API, ext:core