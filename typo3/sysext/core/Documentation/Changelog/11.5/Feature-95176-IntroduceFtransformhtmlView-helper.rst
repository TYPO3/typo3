.. include:: /Includes.rst.txt

==========================================================
Feature: #95176 - Introduce <f:transform.html> view helper
==========================================================

See :issue:`95176`

Description
===========

Using Fluid view-helper :html:`<f:format.html>` provides capabilities to
resolve `t3://` URIs, which is used in backend contexts as well. Internally
:html:`<f:format.html>` relies on an existing frontend context, with
corresponding TypoScript configuration in :typoscript:`lib.parseFunc` being given.

In order to separate concerns better, a new :html:`<f:transform.html>`
view helper has been introduced

*   to be used in frontend and backend context without relying on TypoScript,
*   to avoid mixing parsing, sanitization and transformation concerns in
    previously used :php:`ContentObjectRenderer::parseFunc` method of the
    frontend rendering process.

Impact
======

Individual TYPO3 link handlers (like `t3://` URIs) can be resolved and
substituted without relying on TypoScript configuration and without mixing
concerns in :php:`ContentObjectRenderer::parseFunc` by using Fluid view-helper
:html:`<f:transform.html>`.

Syntax
------

:html:`<f:transform.html selector="[ node.attr, node.attr ]" onFailure="[ behavior ]">`

*   `selector`: (optional) comma separated list of node attributes to be considered,
    for example `subjects="a.href,a.data-uri,img.src"` (default `a.href`)
*   `onFailure` (optional) corresponding behavior, in case transformation failed, for example
    URI was invalid or could not be resolved properly (default `removeEnclosure`).
    Based on example :html:`<a href="t3://INVALID">value</a>`. corresponding results
    of each behavior would be like this:

    +   `removeEnclosure`: :html:`value` (removed enclosing tag)
    +   `removeTag`: :html:`` (removed tag, incl. child nodes)
    +   `removeAttr`: :html:`<a>value</a>` (removed attribute)
    +   `null`: :html:`<a href="t3://INVALID">value</a>` (unmodified, as given)

Example
-------

.. code-block:: html

   <f:transform.html selector="a.href,div.data-uri">
     <a href="t3://page?uid=1" class="page">visit</a>
     <div data-uri="t3://page?uid=1" class="page trigger">visit</div>
   </f:transform.html>

... will be resolved and transformed to the following markup ...

.. code-block:: html

   <a href="https://typo3.localhost/" class="page">visit</a>
   <div data-uri="https://typo3.localhost/" class="page trigger">visit</div>

.. index:: Backend, Fluid, Frontend, ext:fluid
