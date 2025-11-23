..  include:: /Includes.rst.txt

..  _feature-107871-1761597106:

=========================================================
Feature: #107871 - Autoconfigure backend avatar providers
=========================================================

See :issue:`107871`

Description
===========

Backend avatar providers must either use the PHP attribute
:php:`#[AsAvatarProvider]` or be manually tagged in the service container with
:yaml:`backend.avatar_provider`.

When autoconfiguration is enabled in :file:`Services.yaml` or
:file:`Services.php`, applying :php:`#[AsAvatarProvider]` will automatically add
the :yaml:`backend.avatar_provider` tag. Otherwise, the tag must be configured
manually.

Example
-------

..  code-block:: php
    :caption: EXT:my_extension/Classes/Backend/Avatar/MyAvatarProvider.php

    use TYPO3\CMS\Backend\Attribute\AsAvatarProvider;
    use TYPO3\CMS\Backend\Backend\Avatar\AvatarProviderInterface;

    #[AsAvatarProvider(
        'my_provider',
        before: ['provider-1'],
        after: ['provider-2']
    )]
    final class MyAvatarProvider implements AvatarProviderInterface
    {
        // ...
    }

Impact
======

Backend avatar providers are now automatically registered using the PHP
attribute :php:`#[AsAvatarProvider]`. This improves the developer experience and
reduces configuration overhead. The previous registration method via
:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['avatarProviders']` can
no longer be used.

To support multiple TYPO3 Core versions simultaneously, extensions may still
implement the legacy array-based registration alongside the new
autoconfiguration-based approach.

..  index:: Backend, PHP-API, ext:backend
