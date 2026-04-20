..  include:: /Includes.rst.txt

..  _feature-100887-1773012077:

===========================================================
Feature: #100887 - Prefer CSP hash values over nonce values
===========================================================

See :issue:`100887`

Description
===========

Content-Security-Policy nonce values are random tokens in each request that prevent
HTTP response caching. By collecting hash values of assets at render time
instead, responses can be cached, for example by using
:composer:`lochmueller/staticfilecache` or reverse proxies, while still
enforcing a strict CSP.

Hash-based CSP is an explicit opt-in configured for a site via :file:`csp.yaml`.
Nonce values remain the default when no behavior is configured.

New `DirectiveHashCollection` service
-------------------------------------

The new
:php:`\TYPO3\CMS\Core\Security\ContentSecurityPolicy\DirectiveHashCollection`
service is a per-request registry that collects CSP hash values for inline and
static assets during page rendering.

Both inline content and static file resources are supported:

*   Inline assets: the SHA-256 hash is computed over the content that appears
    inside the :html:`<script>` or :html:`<style>` element.
*   Static assets: if an :html:`integrity` attribute is already present, its
    value is reused; otherwise, the file content is hashed on demand.
*   Style attributes: the new :html:`f:asset.styleAttr` ViewHelper hashes
    inline style values and covers the :csp:`style-src-attr` directive.

The collected hashes survive the frontend page cache round-trip via
:php:`\TYPO3\CMS\Frontend\Cache\MetaDataState`.

Updated `Behavior` class
------------------------

:php:`\TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration\Behavior`
now carries a second nullable boolean property, :php:`$useHash`:

*   :php:`true` explicitly enables hash collection and CSP hash sources.
*   :php:`null` means off, which is the default. Hashes are not collected or
    applied.
*   :php:`false` explicitly disables hash collection and CSP hash sources.

Configuring behavior via `csp.yaml`
-----------------------------------

Both :php:`$useNonce` and :php:`$useHash` can be set in a site's
:file:`config/sites/<site>/csp.yaml` under the top-level :yaml:`behavior:` key:

..  code-block:: yaml

    behavior:
      useNonce: false
      useHash: true

    enforce:
      inheritDefault: true
      includeResolutions: true

Setting :yaml:`useHash: true` enables hash-based CSP for that site.
Setting :yaml:`useNonce: false` removes nonce sources from the compiled
policy, which is required for responses to be cacheable by reverse proxies.

Updated `Policy::prepare()` and `Policy::compile()`
---------------------------------------------------

Both methods now accept a
:php:`\TYPO3\CMS\Core\Security\ContentSecurityPolicy\Middleware\PolicyBag`
instead of separate :php:`ConsumableNonce`, :php:`Behavior`, and
:php:`HashCollection` arguments. The :php:`PolicyBag` is forwarded directly
from the CSP middleware, making hash collection visible to PSR-14 event
listeners via
:php:`PolicyPreparedEvent::$policyBag->directiveHashCollection`.

The behavior resolution, applying collected hashes and suppressing nonce
sources, now happens inside :php:`Policy::prepare()`.

New `f:asset.styleAttr` ViewHelper
----------------------------------

A new ViewHelper registers inline style values using the
:csp:`style-src-attr` CSP directive:

..  code-block:: html

    <div style="{f:asset.styleAttr(value: 'color: green', csp: true)}"></div>

The :html:`csp` argument defaults to :html:`true` and controls whether the
hash is collected.

Updated `f:asset.script` and `f:asset.css` ViewHelpers
------------------------------------------------------

The :html:`useNonce` argument has been renamed to :html:`csp`
(deprecated, see :ref:`deprecation-100887-1774712028`). The new default is
:html:`true` for external files, that is, static resources, and
:html:`false` for inline content.

..  code-block:: html

    <!-- static file: csp=1 by default, hash collected from file content -->
    <f:asset.script
        identifier="my-script"
        src="EXT:my_ext/Resources/Public/JavaScript/foo.js"
    />

    <!-- with integrity attribute: hash reused directly, no file read -->
    <f:asset.script
        identifier="my-script"
        src="EXT:my_ext/Resources/Public/JavaScript/foo.js"
        integrity="sha256-abc123=="
    />

    <!-- inline script: opt in explicitly -->
    <f:asset.script identifier="my-inline" csp="1">
        document.querySelector('.foo').classList.add('active');
    </f:asset.script>

Migration
=========

The :html:`useNonce` ViewHelper argument and :php:`'useNonce'` asset option key
are deprecated and replaced by :html:`csp` and :php:`'csp'`. See
:ref:`deprecation-100887-1774712028`.

The signature of :php:`Policy::prepare()` and :php:`Policy::compile()` has
changed to accept a :php:`PolicyBag`. Code calling these methods directly, as
they are marked :php:`@internal`, must be updated.

Impact
======

Sites that configure :yaml:`behavior.useHash: true`, and optionally
:yaml:`behavior.useNonce: false`, in their :file:`csp.yaml` can use hash-based
CSP sources. This allows HTTP responses to be cached by reverse proxies and
static file cache extensions without sacrificing Content-Security-Policy
enforcement. Sites without this configuration continue to use nonce-based CSP.

..  index:: Backend, Frontend, PHP-API, FluidViewHelpers, ext:core, ext:fluid
