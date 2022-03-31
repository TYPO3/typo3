.. include:: /Includes.rst.txt

=================================================
Feature: #93825 - Rate limiting for failed logins
=================================================

See :issue:`93825`

Description
===========

The TYPO3 backend and frontend login now uses a rate limiter by default,
which prevents further authentication attempts for an IP address,
if a configurable amount of login attempts is exceeded in a given time.

The hardcoded wait time of 5 seconds after a failed login has been removed,
since it offers no real protection against brute force attacks and may
result in unwanted side effects.

.. important::

   Rate limiters do not provide a useful protection against DoS attacks.
   They should be used to limit the amount of requests to certain routes
   (for example login or form submission) of an application.

Impact
======

TYPO3 ships with a rate limiter for backend and frontend authentication.
It implements the "Sliding Window Rate Limiter" allowing to define a
maximum amount of login attempts for a given range of time before further login
attempts will be denied for the remote IP address.

A configurable list of IP addresses allows to exclude certain IP addresses or IP
address blocks from being rate limited.

The rate limiter utilizes the TYPO3 caching framework as storage for
rate limiter states. The rate limiter takes care of garbage collection
for affected cache tables on every login request.

Note, that clearing the system cache will purge all limiter states.

Backend login
-------------

The rate limiter :php:`Symfony\Component\RateLimiter\LoginRateLimiter`
for the TYPO3 backend login is enabled by
default and configured with the following default values:

*  Maximum 5 login attempts for a timeframe of 15 minutes
*  No IP address excluded

When the maximum amount of login attempts has exceeded, a
:php:`\TYPO3\CMS\Core\RateLimiter\RequestRateLimitedException`
exception is thrown. The exception implements
:php:`\TYPO3\CMS\Core\Error\Http\AbstractClientErrorException`
resulting in a user readable error message together with a 403 HTTP status code.

Configuration
~~~~~~~~~~~~~

The rate limiter for the TYPO3 backend can be configured using the
Settings module or the Install tool. The following new configuration
values are available:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['BE']['loginRateLimit'] = 5;
   $GLOBALS['TYPO3_CONF_VARS']['BE']['loginRateLimitInterval'] = '15 minutes';
   $GLOBALS['TYPO3_CONF_VARS']['BE']['loginRateLimitIpExcludeList'] = '';

Setting `[BE][loginRateLimit] = 0` will disable rate limiting. The same
applies, if `[BE][loginRateLimitIpExcludeList] = '*'` is configured.

The provided defaults for `[BE][loginRateLimitInterval]` can be customized
in `AdditionalConfiguration.php` by configuring a date/time string following
PHP relative formats.

Frontend login
--------------

The rate limiter for the TYPO3 frontend login is enabled by
default and configured with the following default values:

*  Maximum 10 login attempts for a timeframe of 15 minutes
*  No IP address exclude list

When the maximum amount of login attempts is exceeded, a
:php:`\TYPO3\CMS\Core\RateLimiter\RequestRateLimitedException`
exception is thrown. The exception implements
:php:`\TYPO3\CMS\Core\Error\Http\AbstractClientErrorException`
resulting in a user readable error message together with a 403 HTTP status code.

Configuration
~~~~~~~~~~~~~

Configuration is similar to the rate limiter for the backend login.

The following new configuration values are available:

.. code-block:: php

   $GLOBALS['TYPO3_CONF_VARS']['FE']['loginRateLimit'] = 10;
   $GLOBALS['TYPO3_CONF_VARS']['FE']['loginRateLimitInterval'] = '15 minutes';
   $GLOBALS['TYPO3_CONF_VARS']['FE']['loginRateLimitIpExcludeList'] = '';

.. index:: Backend, Frontend, ext:core
