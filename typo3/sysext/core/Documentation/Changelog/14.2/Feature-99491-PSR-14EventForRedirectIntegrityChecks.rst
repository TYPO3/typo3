.. include:: /Includes.rst.txt

.. _feature-99491-1771891200:

============================================================
Feature: #99491 - PSR-14 event for redirect integrity checks
============================================================

See :issue:`99491`

Description
===========

A new PSR-14 event
:php:`\TYPO3\CMS\Redirects\Event\RedirectIntegrityCheckEvent` has been
added. It is dispatched in
:php:`\TYPO3\CMS\Redirects\Service\IntegrityService->checkRedirectTargetIntegrity()`
for each redirect record.

While the existing integrity check verifies only whether redirect sources
conflict with page URLs (self-reference), this event allows
extensions to validate redirects for other conflict types. For example, an
extension can check whether a `t3://record` link target still resolves correctly
or whether an external URL returns a valid response.

The event provides the following methods:

*   :php:`getRedirect()`: The full :sql:`sys_redirect` record as an array.
*   :php:`getUid()`: Convenience method returning the redirect `uid` as an
    integer.
*   :php:`getPid()`: Convenience method returning the redirect `pid` as an
    integer.
*   :php:`getDeleted()`: Convenience method returning the redirect `deleted`
    as a boolean.
*   :php:`getDisabled()`: Convenience method returning the redirect `disabled`
    as a boolean.
*   :php:`getSourceHost()`: Convenience method returning the redirect
    `source_host` as a string.
*   :php:`getSourcePath()`: Convenience method returning the redirect
    `source_path` as a string.
*   :php:`getIsRegExp()`: Convenience method returning the redirect
    `is_regexp` as a boolean.
*   :php:`getProtected()`: Convenience method returning the redirect
    `protected` as a boolean.
*   :php:`getForceHttps()`: Convenience method returning the redirect
    `force_https` as a boolean.
*   :php:`getRespectQueryParameters()`: Convenience method returning the
    redirect `respect_query_parameters` as a boolean.
*   :php:`getKeepQueryParameters()`: Convenience method returning the redirect
    `keep_query_parameters` as a boolean.
*   :php:`getTarget()`: Convenience method returning the redirect `target` as
    a string.
*   :php:`getTargetStatusCode()`: Convenience method returning the redirect
    `target_statuscode` as an integer.
*   :php:`getCreationType()`: Convenience method returning the redirect
    `creation_type` as an integer.
*   :php:`getOriginalIntegrityStatus()`: Convenience method returning the
    redirect `integrity_status` as a string.
*   :php:`getIntegrityStatus()` / :php:`setIntegrityStatus()`: Read or set the
    integrity status. When a listener sets a non-null status, the redirect is
    reported as a conflict in the :bash:`redirects:checkintegrity` command
    output. Be aware that
    :php:`\TYPO3\CMS\Redirects\Utility\RedirectConflict::NO_CONFLICT` can be
    set as the integrity status and will not be included in the report, even
    if a listener explicitly sets
    :php-short:`\TYPO3\CMS\Redirects\Utility\RedirectConflict::NO_CONFLICT`
    for the redirect.

Additionally, the following new class constant has been added to allow
extensions to conveniently reuse a shared conflict status in custom event
listeners:

*   :php:`\TYPO3\CMS\Redirects\Utility\RedirectConflict::INVALID_TARGET`

Example
=======

An event listener that validates `t3://record` targets:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ValidateRedirectTarget.php

    <?php

    namespace MyVendor\MyExtension\EventListener;

    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Redirects\Event\RedirectIntegrityCheckEvent;
    use TYPO3\CMS\Redirects\Utility\RedirectConflict;

    final readonly class ValidateRedirectTarget
    {
        public function __construct(
            private ConnectionPool $connectionPool,
        ) {}

        #[AsEventListener('my-extension/validate-redirect-target')]
        public function __invoke(RedirectIntegrityCheckEvent $event): void
        {
            $target = $event->getTarget();
            if (!str_starts_with($target, 't3://record')) {
                return;
            }
            // Parse t3://record?identifier=tx_news&uid=456
            parse_str((string)parse_url($target, PHP_URL_QUERY), $params);
            $table = $params['identifier'] ?? '';
            $uid = (int)($params['uid'] ?? 0);
            if ($table === '' || $uid === 0) {
                $event->setIntegrityStatus(RedirectConflict::INVALID_TARGET);
                return;
            }
            $count = $this->connectionPool
                ->getConnectionForTable($table)
                ->count('uid', $table, ['uid' => $uid]);
            if ($count === 0) {
                $event->setIntegrityStatus(RedirectConflict::INVALID_TARGET);
                return;
            }
            // Set to NO_CONFLICT. This will not be reported as a conflicting
            // redirect, but it clears any previously set integrity status.
            $event->setIntegrityStatus(RedirectConflict::NO_CONFLICT);
        }
    }

Impact
======

Extensions can now validate redirects during the integrity check by listening
to this event. Broken or invalid redirects are reported alongside existing
self-reference conflicts in the :bash:`redirects:checkintegrity` command
output.

.. index:: CLI, PHP-API, ext:redirects
