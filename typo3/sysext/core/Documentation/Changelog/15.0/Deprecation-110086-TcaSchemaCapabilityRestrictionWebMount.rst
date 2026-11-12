..  include:: /Includes.rst.txt

..  _deprecation-110086-1794514228:

===============================================================
Deprecation: #110086 - TcaSchemaCapability::RestrictionWebMount
===============================================================

See :issue:`110086`

Description
===========

The enum case :php:`\TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability::RestrictionWebMount`
has been marked as deprecated and will be removed in TYPO3 v16.0.

Despite its name, the case never represented a restriction. It was meant to be
the inversion of :php:`TCA[ctrl][security][ignoreWebMountRestriction]`, but it
has always behaved exactly like that option: it is :php:`true` for tables that
ignore the web mount restriction.

The new enum case
:php:`\TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability::IgnoreWebMountRestriction`
carries the same value and the name states what it means.

Impact
======

Using :php:`TcaSchemaCapability::RestrictionWebMount` continues to work and
returns the same value as before. The case will be removed in TYPO3 v16.0.

Affected installations
======================

TYPO3 installations with custom extensions or code that use
:php:`TcaSchemaCapability::RestrictionWebMount`, for example in calls to
:php:`TcaSchema->hasCapability()` or :php:`TcaSchema->getCapability()`, are
affected.

The extension scanner will report any usage as a **strong match**.

Migration
=========

Replace :php:`TcaSchemaCapability::RestrictionWebMount` with
:php:`TcaSchemaCapability::IgnoreWebMountRestriction`. The returned value is
identical, so no inversion of the surrounding condition is needed.

..  code-block:: php
    :caption: Before (deprecated)

    $ignoreWebMountRestriction = $schema->hasCapability(TcaSchemaCapability::RestrictionWebMount);

..  code-block:: php
    :caption: After (recommended)

    $ignoreWebMountRestriction = $schema->hasCapability(TcaSchemaCapability::IgnoreWebMountRestriction);

..  index:: PHP-API, FullyScanned, ext:core
