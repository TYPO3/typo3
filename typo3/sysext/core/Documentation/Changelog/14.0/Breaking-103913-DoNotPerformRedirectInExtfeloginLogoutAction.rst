..  include:: /Includes.rst.txt

..  _breaking-103913-1740920635:

=======================================================================
Breaking: #103913 - Do not perform redirect in EXT:felogin logoutAction
=======================================================================

See :issue:`103913`

Description
===========

Redirect handling for the :php:`logoutAction` has been removed.

Impact
======

The :php:`logoutAction` no longer performs any configured redirect via plugin
settings or GET parameters.

Affected installations
======================

TYPO3 installations relying on redirect handling within
:php:`logoutAction` are affected.

Migration
=========

No migration is required. The previous redirect logic in
:php:`logoutAction()` has been removed because it was incorrect: it ignored
the :php:`showLogoutFormAfterLogin` setting and could trigger an unintended
redirect even when this option was enabled.

Valid redirects are already processed correctly by
:php:`loginAction()` and :php:`overviewAction()`, so the faulty branch was
removed without replacement.

..  index:: Frontend, NotScanned, ext:felogin
