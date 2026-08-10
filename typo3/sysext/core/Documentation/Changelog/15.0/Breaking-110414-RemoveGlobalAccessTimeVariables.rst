.. include:: /Includes.rst.txt

.. _breaking-110414-1786380424:

=======================================================
Breaking: #110414 - Remove global access time variables
=======================================================

See :issue:`110414`

Description
===========

The global variables :php:`$GLOBALS['ACCESS_TIME']` and
:php:`$GLOBALS['SIM_ACCESS_TIME']` have been removed.

Both were introduced in 2008 and held the current (respectively the simulated)
execution time floored to the full minute. Their only purpose was to keep the
SQL of :sql:`starttime` / :sql:`endtime` comparisons textually identical for up
to 60 seconds, so that the MySQL query cache could serve repeated requests. That
query cache has been removed from MySQL and is disabled by default in MariaDB,
so the original motivation no longer applies.

Beyond that, both values were pure derivatives of :php:`$GLOBALS['EXEC_TIME']`
and :php:`$GLOBALS['SIM_EXEC_TIME']` that had to be kept in sync manually, which
was an error-prone contract.

The minute granularity itself is kept, since record visibility and the frontend
cache lifetime derived from :sql:`starttime` / :sql:`endtime` must use the very
same clock. It now lives in the date aspect of the Context API, which gained two
typed methods:

*  :php:`\TYPO3\CMS\Core\Context\DateTimeAspect::getTimestamp()`
*  :php:`\TYPO3\CMS\Core\Context\DateTimeAspect::getTimestampWithMinutePrecision()`

The already existing property :php:`$context->getPropertyFromAspect('date', 'accessTime')`
is unchanged and now delegates to the latter method.

As a consequence, :php:`\TYPO3\CMS\Core\Database\Query\Restriction\StartTimeRestriction`
and :php:`\TYPO3\CMS\Core\Database\Query\Restriction\EndTimeRestriction` fall back
to the date aspect instead of the global variable when no timestamp is handed in.
The :php:`\RuntimeException` with codes :php:`1462820645` and :php:`1462821084`,
which was thrown when the global was missing, has become unreachable and has been
removed.

Impact
======

Reading :php:`$GLOBALS['ACCESS_TIME']` or :php:`$GLOBALS['SIM_ACCESS_TIME']`
raises an "Undefined array key" warning and evaluates to :php:`null`.

Writing to either variable no longer has any effect. In particular, simulating
another point in time by setting :php:`$GLOBALS['SIM_ACCESS_TIME']` is silently
ignored, and records are then evaluated against the real current time.

The following constructors received an additional
:php:`\TYPO3\CMS\Core\Context\Context` argument:

*  :php:`\TYPO3\CMS\Frontend\Cache\CacheLifetimeCalculator`
*  :php:`\TYPO3\CMS\Redirects\Service\RedirectService`
*  :php:`\TYPO3\CMS\Backend\Controller\PageLayoutController`

Affected installations
======================

Installations with extensions that read or write one of the two global
variables, that rely on the removed exceptions of the start and end time
restrictions, or that instantiate one of the above classes manually instead of
using dependency injection.

Migration
=========

Use the date aspect of the Context API instead of the global variables:

..  code-block:: php

    use TYPO3\CMS\Core\Context\Context;

    // Before
    $accessTime = $GLOBALS['SIM_ACCESS_TIME'];

    // After, with dependency injection
    public function __construct(private readonly Context $context) {}

    $accessTime = $this->context->getAspect('date')->getTimestampWithMinutePrecision();

In places without dependency injection, the current context is fetched via
:php:`GeneralUtility::makeInstance(Context::class)`.

To simulate another point in time, set the date aspect instead of writing to
:php:`$GLOBALS['SIM_ACCESS_TIME']`:

..  code-block:: php

    use TYPO3\CMS\Core\Context\DateTimeAspect;
    use TYPO3\CMS\Core\Domain\DateTimeFactory;

    $context->setAspect('date', new DateTimeAspect(DateTimeFactory::createFromTimestamp($timestamp)));

.. index:: PHP-API, NotScanned, ext:core
