..  include:: /Includes.rst.txt

..  _feature-102079-1756482906:

===========================================================================
Feature: #102079 - Introduce BeforePersistingReportEvent for CSP violations
===========================================================================

See :issue:`102079`

Description
===========

When a Content-Security-Policy violation report needs to be persisted, the
:php:`\TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\BeforePersistingReportEvent`
can be used to provide an alternative report or to prevent a particular report
from being persisted at all.

Example
-------

..  code-block:: php

    <?php
    declare(strict_types=1);

    namespace Example\Demo\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Event\BeforePersistingReportEvent;
    use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting\Report;

    final class BeforePersistingReportEventListener
    {
        private const BROWSER_PREFIXES = [
            'chrome-extension://',
            'moz-extension://',
            'safari-extension://',
        ];

        #[AsEventListener('example/security/before-persisting-csp-report')]
        public function __invoke(BeforePersistingReportEvent $event): void
        {
            // avoid persisting CSP violations that were caused by browser extensions
            $blockedUri = $event->originalReport->details['blocked-uri'] ?? null;
            if (is_string($blockedUri) && $this->isBrowserExtensions($blockedUri)) {
                $event->report = null;
                return;
            }
            // otherwise adjust report and provide custom meta-data
            $event->report = new Report(
                $event->originalReport->scope,
                $event->originalReport->status,
                $event->originalReport->requestTime,
                array_merge(
                    $event->originalReport->meta,
                    ['x-example' => '... additional meta-data ...']
                ),
                $event->originalReport->details,
                $event->originalReport->summary,
                $event->originalReport->uuid,
                $event->originalReport->created,
                $event->originalReport->changed
            );
        }

        private function isBrowserExtensions(string $blockedUri): bool
        {
            foreach (self::BROWSER_PREFIXES as $prefix) {
                if (str_starts_with($blockedUri, $prefix)) {
                    return true;
                }
            }
            return false;
        }
    }


Impact
======

The new `BeforePersistingReportEvent` allows custom control over whether and how
Content-Security-Policy violation reports are persisted in TYPO3.

..  index:: Backend, Frontend, PHP-API, ext:core
