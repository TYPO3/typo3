..  include:: /Includes.rst.txt

..  _important-107062-1759872067:

===========================================================================================
Important: #107062 - Avoid applying Content-Security-Policy nonce sources when not required
===========================================================================================

See :issue:`107062`

Description
===========

Using nonce sources in a Content-Security-Policy (CSP) HTTP header implicitly leads
to having a `Cache-Control: private, no-store` HTTP response header and internally
requires to renew the nonce value that is present in cached HTML contents, which
has a negative impact on performance.

This change aims for having fully cached pages and tries to avoid nonce sources
in the CSP header when actually feasible.

* The :php:`ConsumableNonce` class was refactored – it no longer extends
  :php:`ConsumableString`.
* Two new counters are introduced:
  :php:`consumeInline()`  – the nonce is **required** for an inline resource.
  :php:`consumeStatic()` – the nonce is **optional** for a static resource.

Example usage:

..  code-block:: php

    <?php
    $nonce = new ConsumableNonce();
    $nonce->consumeInline(Directive::ScriptSrcElem); // inline script
    $nonce->consumeStatic(Directive::StyleSrcElem);  // static style

Nonce sources are removed from the CSP policy in the following
situations, in case the request is supposed to be fully cacheable
(`config.no_cache = 0` and not having any `USER_INT` or `COA_INT` items):

* The response body is readable and contains **no** bytes.
* The nonce consumption counter for **all** usages equals zero.
* A directive contains a source‑keyword exception (e.g. `'unsafe-inline'`)
  that makes a nonce unnecessary.
* The :php:`PolicyPreparedEvent` has been dispatched and explicitly tells
  the policy to avoid using nonce sources.

When the nonce should be removed, both the frontend and backend
:php:`ContentSecurityPolicyHeaders` middleware strip the nonce-related
literals from the rendered HTML.

New PSR-14 event
----------------

..  code-block:: php

    <?php
    declare(strict_types=1);

    namespace Example\MyPackage\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Disposition;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\PolicyPreparedEvent;

    #[AsEventListener('my-package/content-security-policy/avoid-nonce')]
    final class DropNonceEventListener
    {
        public function __invoke(PolicyPreparedEvent $event): void
        {
            $policyBag = $event->policyBag;
            if (
                isset($policyBag->dispositionMap[Disposition::enforce])
                && $policyBag->scope->siteIdentifier === 'my-special-site'
                // YOLO: drop nonce sources, even it is consumed
                && $policyBag->nonce->count() > 0
            ) {
                $policyBag->behavior->useNonce = false;
            }
        }
    }

New :php:`useNonce` property
----------------------------

The :php:`\TYPO3\CMS\Core\Security\ContentSecurityPolicy\Configuration\Behavior`
class now contains the nullable boolean property :php:`useNonce`:

* :php:`true` - explicitly allows using nonce sources
* :php:`null` - the default unspecific state (the system will detect and decide automatically)
* :php:`false` - explicitly denies using nonce sources, it also drops constraints like
  `'strict-dynamic'` since that source keyword requires a nonce source

..  index:: Backend, Frontend, PHP-API, ext:core
