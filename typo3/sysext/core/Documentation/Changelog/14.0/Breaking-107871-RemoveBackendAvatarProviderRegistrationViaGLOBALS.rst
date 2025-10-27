..  include:: /Includes.rst.txt

..  _breaking-107871-1761597102:

===================================================================================
Breaking: #107871 - Remove backend avatar provider registration via :php:`$GLOBALS`
===================================================================================

See :issue:`107871`

Description
===========

Registering backend avatar providers via
:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['avatarProviders']`
has been replaced by autoconfiguration using the :yaml:`backend.avatar_provider`
service tag. This tag is added automatically when the PHP attribute
:php:`#[AsAvatarProvider]` is applied. Manual configuration in
:file:`Services.yaml` is possible, particularly if autoconfiguration is
disabled.

Impact
======

Utilizing the :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['avatarProviders']`
array has no effect in TYPO3 v14.0 and later.


Affected installations
======================

All installations that use :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['avatarProviders']`
to register backend avatar providers are affected. This registration is
typically performed in an :file:`ext_localconf.php` file. The extension
scanner will report any such usages.

Migration
=========

Migrate existing registrations to the new autoconfigured-based approach.

**Before:**

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    use Vendor\MyExtension\Backend\Avatar\MyAvatarProvider;

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['backend']['avatarProviders']['my_provider'] = [
        'provider' => MyAvatarProvider::class,
        'before' => ['provider-1'],
        'after' => ['provider-2'],
    ];

**After:**

..  code-block:: php
    :caption: EXT:my_extension/Classes/Backend/Avatar/MyAvatarProvider.php

    use TYPO3\CMS\Backend\Attribute\AsAvatarProvider;
    use TYPO3\CMS\Backend\Backend\Avatar\AvatarProviderInterface;

    #[AsAvatarProvider('my_provider', before: ['provider-1'], after: ['provider-2'])]
    final class MyAvatarProvider implements AvatarProviderInterface
    {
        // ...
    }

If you need to support multiple TYPO3 Core versions simultaneously, ensure that
both registration methods are implemented: the legacy registration via
:php:`$GLOBALS` as well as the new tag-based approach.

..  index:: Backend, PHP-API, FullyScanned, ext:backend
