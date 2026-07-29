.. include:: /Includes.rst.txt

.. _breaking-110250-1785315899:

===================================================================
Breaking: #110250 - Remove implicit output buffering from Bootstrap
===================================================================

See :issue:`110250`

Description
===========

The class :php:`\TYPO3\CMS\Core\Core\Bootstrap` no longer wraps the entire
bootstrap and request lifecycle in an implicit output buffer via
:php:`ob_start()`. This buffer was historically opened as the very first
action in :php:`Bootstrap::init()` to swallow accidental stray output
(warnings, notices or stray :php:`echo`/:php:`print` statements, typically
from :php:`ext_localconf.php`/:php:`ext_tables.php` of third-party
extensions) so it would not corrupt AJAX responses or file downloads. On
CLI this buffering was already disabled again in a previous change, as it
delayed console output until the process ended and could bloat memory for
commands producing a lot of output.

Since responses are fully assembled as PSR-7 message bodies before being
emitted, this legacy safety net is not needed anymore.

Along with it, the now unused method
:php:`Bootstrap::startOutputBuffering()` has been removed.

Impact
======

Any stray output produced during bootstrap (for example from
:php:`ext_localconf.php`) is now sent to the client directly instead of
being silently discarded later on. This mainly affects binary/file
responses and AJAX requests, which relied on the buffer being cleaned
right before the response body was emitted.

Calling :php:`Bootstrap::startOutputBuffering()` now results in a fatal
PHP error (:php:`Call to undefined method`).

Affected installations
======================

TYPO3 installations with extensions that produce accidental output during
:php:`ext_localconf.php`/:php:`ext_tables.php` loading, which so far was
silently swallowed, and installations with custom entry point scripts
calling :php:`Bootstrap::startOutputBuffering()` directly.

Migration
=========

Remove any accidental output from :php:`ext_localconf.php` and
:php:`ext_tables.php` files. Extensions that need to suppress their own
output must manage output buffering (:php:`ob_start()` /
:php:`ob_end_clean()`) themselves.

Remove calls to :php:`Bootstrap::startOutputBuffering()` and use
:php:`ob_start()` directly, if the behaviour is still required.

.. index:: PHP-API, FullyScanned, ext:core
