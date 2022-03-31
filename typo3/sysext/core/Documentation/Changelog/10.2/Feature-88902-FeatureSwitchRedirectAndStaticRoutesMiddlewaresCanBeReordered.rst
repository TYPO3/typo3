.. include:: /Includes.rst.txt

=========================================================================================
Feature: #88902 - Feature Switch: Redirect and Base Redirect Middlewares can be reordered
=========================================================================================

See :issue:`88902`

Description
===========

A new feature switch :php:`rearrangedRedirectMiddlewares` has been introduced to rearrange the middlewares
:php:`typo3/cms-redirects/redirecthandler` and :php:`typo3/cms-frontend/base-redirect-resolver`. If enabled, the
middlewares are executed in reversed order, so the :php:`typo3/cms-redirects/redirecthandler` comes first.

The new ordering aims to be a better default shipped by the TYPO3 core but might still need adjustment due to specific
configuration setups.

The feature switch is turned off by default to assure a non-breaking behaviour.

Impact
======

If turned on, the new ordering has the following implications:

By putting the :php:`typo3/cms-frontend/base-redirect-resolver` last, redirects are always resolved even if no
configured base URL was requested. In most cases this is considered to be a bugfix. However, redirect behavior might
change.

Custom middlewares that have been put in between the two above mentioned middlewares most likely will lead to a circular
dependency exception now. Such custom middlewares have to be revisited and registered differently.

.. index:: Frontend, NotScanned, ext:redirects
