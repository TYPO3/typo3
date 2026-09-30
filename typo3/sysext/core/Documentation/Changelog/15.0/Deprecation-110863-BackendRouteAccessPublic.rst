..  include:: /Includes.rst.txt

..  _deprecation-110863-1790766913:

====================================================
Deprecation: #110863 - Backend route access "public"
====================================================

See :issue:`110863`

Description
===========

The value :php:`public` of the backend route option :php:`access` has been
deprecated. It only omitted the request token of a route, while the backend
user was still required for all routes not listed in the core. Therefore, the
name did not describe the behaviour.

The new values :php:`anonymous` (no backend user, no request token) and
:php:`authenticated-without-token` (backend user, but no request token) replace
it, see :ref:`feature-110863-1790781220`.

Impact
======

Routes declaring :php:`'access' => 'public'` are still registered with the
unchanged behaviour of :php:`authenticated-without-token`, and a PHP
deprecation is triggered when the route is registered.

Affected installations
======================

Installations with extensions that register backend routes in
:file:`Configuration/Backend/Routes.php` or
:file:`Configuration/Backend/AjaxRoutes.php` with :php:`'access' => 'public'`.

Migration
=========

Replace :php:`'access' => 'public'` with
:php:`'access' => 'authenticated-without-token'` to keep the current behaviour,
which requires a backend user, but no request token.

Use :php:`'access' => 'anonymous'` only if the route must be reachable without
a backend user, for example the callback of a single sign-on provider.

..  index:: Backend, PHP-API, NotScanned, ext:backend
