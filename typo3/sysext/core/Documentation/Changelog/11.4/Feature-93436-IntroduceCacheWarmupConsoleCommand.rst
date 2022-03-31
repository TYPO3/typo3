.. include:: /Includes.rst.txt

========================================================
Feature: #93436 - Introduce cache:warmup console command
========================================================

See :issue:`93436`

Description
===========

It is now possible to warmup TYPO3 caches using the command line.

The administrator can use the following CLI command:

.. code-block:: bash

   ./typo3/sysext/core/bin/typo3 cache:warmup

Specific cache groups can be defined via the group option.
The usage is described as:

.. code-block:: bash

   cache:warmup [--group <all|system|di|pages|…>]

All available cache groups can be supplied as option. The command defaults to
warm all available cache groups.

Extensions that register custom caches are encouraged to implement cache warmers
via :php:`TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent`.

Note: TYPO3 frontend caches will not be warmed by TYPO3 core, such functionality
could be added by third party extensions with the help of
:php:`TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent`.

Impact
======

It is common practice to clear all caches during deployment of TYPO3 instance
updates. This means that the first request after a deployment usually takes
a major amount of time and blocks other requests due to cache-locks.

TYPO3 caches can now be warmed during deployment in release preparatory steps in
symlink based deployment/release procedures. This enables fast first requests
with all (or at least system) caches being prepared and warmed.

Caches are often filesystem relevant (filepaths are calculated into cache
hashes), therefore cache warmup should only be performed on the the live system,
in the *final* folder of a new release, and ideally before switching
to that new release (via symlink switch). Note that caches that have be
pre-created in CI will likely be useless as cache hashes will not match.

To summarize: Cache warmup is to be used during deployment, on the live system
server, inside the new release folder and before switching the new release live.

Deployment steps are:

* Release preparation:

  * git-checkout/rsync your codebase (on CI or on live system)
  * `composer install` (on CI or on live system)
  * `vendor/bin/typo3 cache:warmup --group system` (*only* on the live system)

* Change release symlink to the new release folder
* Release postparation

  * Clear only the page related caches (e.g. via database truncate or an
    upcoming `cache:flush` command)

The conceptional idea is to warmup all file-related caches *before* (symlink)
switching to a new release and to *only* flush database and frontend (shared)
caches after the symlink switch. Database warmup could be implemented with
the help of the :php:`TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent` as an
additionally functionality by third party extensions.

Note that file-related caches (summarized into the group "system") can safely be
cleared before doing a release switch, as it is recommended to keep file caches
per release. In other words, share :file:`var/session`, :file:`var/log`,
:file:`var/lock` and :file:`var/charset` between releases, but keep
:file:`var/cache` be associated only with one release.

.. index:: CLI, ext:core
