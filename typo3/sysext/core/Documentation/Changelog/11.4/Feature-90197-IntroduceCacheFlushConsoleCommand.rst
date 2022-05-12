.. include:: /Includes.rst.txt

=======================================================
Feature: #90197 - Introduce cache:flush console command
=======================================================

See :issue:`90197`

Description
===========

It is now possible to flush TYPO3 caches using the command line.

The administrator can use the following CLI command:

.. code-block:: bash

   ./typo3/sysext/core/bin/typo3 cache:flush

Specific cache groups can be defined via the group option.
The usage is described as:

.. code-block:: bash

   cache:flush [--group <all|system|di|pages|…>]

All available cache groups can be supplied as option. The command defaults to
flush all available cache groups as the install tool does.

Extensions that register custom caches may listen to the
via :php:`TYPO3\CMS\Core\Cache\Event\CacheFlushEvent`, but usually the
cache flush via CacheManager groups will suffice.

Impact
======

It is often required to clear caches during deployment of TYPO3 instance
updates, in order for content changes to become active.

TYPO3 caches can now be flushed in release postparatory steps. The integrator
may decide to flush all caches (common practice with `EXT:typo3_console`) or
may alternatively flush selected groups (e.g. 'pages') in case the `cache:warmup`
(see :issue:`93436`) command is used as companion in release preparatory steps.

Deployment steps could then be:

* Release preparation:

  * git-checkout/rsync your codebase (on CI or on live system)
  * `composer install` (on CI or on live system)
  * `vendor/bin/typo3 cache:warmup --group system` (*only* on the live system)

* Change release symlink to the new release folder
* Release postparation

  * `vendor/bin/typo3 cache:flush --group pages`

.. index:: CLI, ext:core
