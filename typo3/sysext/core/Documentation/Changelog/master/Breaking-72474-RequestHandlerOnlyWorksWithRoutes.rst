========================================================
Breaking: #72474 - RequestHandler only works with Routes
========================================================

Description
===========

The default backend RequestHandler has had a fallback that triggered the backend initialization without having
a ``&route=`` request parameter set. This was used for the transition when deprecating the traditional entry-scripts. The
logic was removed.


Impact
======

Any regular backend request (non-module and non-AJAX) will now require a ``&route=`` request parameter, otherwise will fallback to the default route (login) when non given.


Migration
=========

For all backend-related calls, either use a custom RequestHandler or switch to using Backend Routing.