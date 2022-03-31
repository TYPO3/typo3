.. include:: /Includes.rst.txt

===============================================================
Breaking: #92352 - New default position for redirect middleware
===============================================================

See :issue:`92352`

Description
===========

TYPO3 10 introduced the feature toggle `rearrangedRedirectMiddlewares` to rearrange the middlewares
:php:`typo3/cms-redirects/redirecthandler` and :php:`typo3/cms-frontend/base-redirect-resolver`. If enabled, the
the :php:`typo3/cms-redirects/redirecthandler` is executed first.

This order has the advantage that any redirect would work regardless whether the request made it through the
:php:`typo3/cms-frontend/base-redirect-resolver`. While this might cause problems in some scenarios it is by
far the better default.

Therefore the feature switch has been removed now and the above described order is the new default.

Impact
======

By putting the :php:`typo3/cms-frontend/base-redirect-resolver` last, redirects are always resolved even if no
configured base URL was requested. In most cases this is considered to be a bugfix. However, redirect behavior might
change.

Custom middlewares that have been put in between the two above mentioned middlewares most likely will lead to a circular
dependency exception now. Such custom middlewares have to be revisited and registered differently.

Affected Installations
======================

All installations that need the :php:`typo3/cms-frontend/base-redirect-resolver` executed before the
:php:`typo3/cms-redirects/redirecthandler` or that have the feature switch turned off and registered
a custom middleware in between the two or with one of the two as a position definition via `after` or `before`.

Migration
=========

Manually check the position of your custom middlewares and adapt accordingly.

If needed the order of the middlewares can be switched back manually as described in the documentation.

.. index:: Frontend, NotScanned, ext:redirects
