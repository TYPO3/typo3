..  include:: /Includes.rst.txt

..  _breaking-103913-1740920635:

=======================================================================
Breaking: #103913 - Do not perform redirect in ext:felogin logoutAction
=======================================================================

See :issue:`103913`

Description
===========

Redirect handling for the :php:`logoutAction` has been removed.


Impact
======

The :php:`logoutAction` will not perform a possible configured redirect via
plugin or GET parameter.


Affected installations
======================

TYPO3 installation relying on redirect handling in :php:`logoutAction`.


Migration
=========

There is no migration path, since the redirect handling in the
:php:`logoutAction` was wrong and possible configured redirects were already
correctly handled via `loginAction` and `overviewAction`.

..  index:: Frontend, NotScanned, ext:felogin
