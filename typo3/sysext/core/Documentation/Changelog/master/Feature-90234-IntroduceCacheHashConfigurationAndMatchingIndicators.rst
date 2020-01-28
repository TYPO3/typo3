.. include:: ../../Includes.txt

==========================================================================
Feature: #90234 - Introduce CacheHashConfiguration and matching indicators
==========================================================================

See :issue:`90234`

Description
===========

Settings for `$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']` are modelled
in class `CacheHashConfiguration` which takes care of validating configuration.
It also determines whether corresponding aspects apply to a given URL
parameter.

Besides exact matches (*equals*) it is possible to apply partial matches at
the beginning of a parameter (*startsWith*) or inline occurrences (*contains*).

URL parameter names are prefixed with the following indicators:
+ `=` (*equals*): exact match, default behavior if not given
+ `^` (*startsWith*): matching the beginning of a parameter name
+ `~` (*contains*): matching any inline occurrence in a parameter name

These indicators can be used for all previously existing sub-properties
`cachedParametersWhiteList`, `excludedParameters`, `excludedParametersIfEmpty`
and `requireCacheHashPresenceParameters`.

Example (excerpt of `LocalConfiguration.php`)
---------------------------------------------

.. code-block:: php
   $GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash'] = [
     'excludedParameters' => [
       'utm_source',
       'utm_medium',
       '^utm_', // making previous two obsolete
     ],
     'excludedParametersIfEmpty' => [
       '^tx_my_plugin[aspects]',
       'tx_my_plugin[filter]',
     ],
   ];


Impact
======

Configuration related to *cHash* URL parameter supports partial matches which
overcomes the previous necessity to explicitly name all parameter names to be
excluded.

For instance instead of having exclude items like

.. code-block:: php

   'excludedParameters' => [
      'tx_my[data][uid]',
      'tx_my[data][category]',
      'tx_my[data][order]',
      'tx_my[data][origin]',
      ...
   ],

partial matches allow to simply configuration and consider all items having
`tx_my[data]` (or `tx_my[data][` to be more specific) as prefix like

.. code-block:: php

   'excludedParameters' => [
      '^tx_my[data][',
      ...
   ],

Previous configuration for the `cHash` section is still supported - there is
no syntactical requirement to adjust those changes.

.. index:: Frontend, LocalConfiguration, ext:frontend
