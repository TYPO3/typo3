.. include:: /Includes.rst.txt

.. _breaking-98488-1664578695:

==========================================================================================
Breaking: #98488 - Typolink option "addQueryString" only includes resolved query arguments
==========================================================================================

See :issue:`98488`

Description
===========

The Typolink option :typoscript:`typolink.addQueryString` previously set all given
GET parameters to a generated URL that were handed in to the request.

This option is also used under the hood for the  Fluid ViewHelpers
:html:`<f:link.typolink>`, :html:`<f:uri.typolink>`, :html:`<f:link.page>`,
:html:`<f:uri.page>`, :html:`<f:link.action>`, :html:`<f:link.action>` and
:html:`<f:form>`.

With TYPO3 v9 and routing, this option now only adds the query arguments that
have been resolved during the routing process. This way, additional query arguments
are never added by default.

Impact
======

Setting :typoscript:`typolink.addQueryString = 1` now adds only arguments resolved
by Route Enhancers, any other query arguments are rejected.

As a consequence, arbitrary query arguments are not reflected in the
canonical link reference anymore. Declaring corresponding route definitions
is required to have those values reflected again.

Affected installations
======================

TYPO3 installations relying on `typolink.addQueryString`.

Migration
=========

It is recommended to keep the setting as is, as TYPO3 can identify valid query
arguments via Routing.

However, to ensure the previous behaviour, the option
:typoscript:`typolink.addQueryString` can be set to `untrusted` to add all given.

The same value is also possible for the Fluid ViewHelpers
:html:`<f:link.typolink>`, :html:`<f:uri.typolink>`, :html:`<f:link.page>`,
:html:`<f:uri.page>`, :html:`<f:link.action>`, :html:`<f:link.action>` and
:html:`<f:form>`.

.. index:: Fluid, TypoScript, NotScanned, ext:frontend
