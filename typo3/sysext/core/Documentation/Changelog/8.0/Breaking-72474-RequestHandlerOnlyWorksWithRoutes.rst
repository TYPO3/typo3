
.. include:: ../../Includes.txt

========================================================
Breaking: #72474 - RequestHandler only works with Routes
========================================================

See :issue:`72474`

Description
===========

The default backend RequestHandler had a fallback that triggered the backend initialization without having
a `&route=` request parameter set. This was used for the transition when deprecating the traditional entry-scripts. The
logic has been removed.


Impact
======

Any regular backend request (non-module and non-AJAX) will now require a
`&route=` request parameter, otherwise will fallback to the default route
(login) when not provided.


Migration
=========

For all backend-related calls, either use a custom RequestHandler or switch to Backend Routing.

.. index:: Backend, PHP-API
