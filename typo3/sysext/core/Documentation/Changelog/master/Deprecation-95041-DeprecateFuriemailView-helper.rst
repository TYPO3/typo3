.. include:: ../../Includes.txt

=========================================================
Deprecation: #95041 - Deprecate <f:uri.email> view-helper
=========================================================

See :issue:`95041`

Description
===========

Fluid view-helper :html:<f:uri.email email="{email}">` was used in combination
with :ts:`config.spamProtectEmailAddresses` settings during frontend rendering
and returned corresponding :js:`javascript:linkTo_UnCryptMailto(...)` inline
JavaScript URI. In case spam-protections is not configured, this view-helper
just passed through the given email address.

In favor of allowing more content security policy scenarios, `javascript:`
URI is not used anymore per default. As a result, :html:`<f:uri.email>`
view-helper became obsolete. The view-helper will be removed with TYPO3 v12.0.


Impact
======

Using :html:<f:uri.email>` view-helper triggers a deprecation level error.


Affected Installations
======================

All projects using :html:<f:uri.email email="{email}">` or
:html:`{email -> f:uri.email}` view-helper invocations in their Fluid templates.


Migration
=========

In case :ts:`config.spamProtectEmailAddresses` is used, make use of
:html:`<f.link.email email="{email}">` view-helper which returns the
complete :html:`<a>` tag like this:

... code-block:: html

    <a href="#" data-mailto-token="ocknvq,hqqBdct0vnf"
        data-mailto-vector="1">user(at)my.example(dot)com</a>

In case spam-protected is not used or not useful (e.g. in backend user
interface), view-helper invocation can be omitted completely.


.. index:: Fluid, Frontend, FullyScanned, ext:fluid
