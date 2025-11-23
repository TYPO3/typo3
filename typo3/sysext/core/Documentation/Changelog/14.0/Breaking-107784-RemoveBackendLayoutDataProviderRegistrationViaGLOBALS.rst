..  include:: /Includes.rst.txt

..  _breaking-107784-1760947244:

===================================================================================
Breaking: #107784 - Remove backend layout data provider registration via `$GLOBALS`
===================================================================================

See :issue:`107784`

Description
===========

The possibility to register backend layout data providers via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']`
has been replaced by autoconfiguration using the service tag
:yaml:`page_layout.data_provider`.

The tag is automatically added when a class implements
:php:`\TYPO3\CMS\Backend\View\BackendLayout\DataProviderInterface`. Manual
configuration via :file:`Services.yaml` remains possible, especially when
autoconfiguration is disabled.

Developers need to adapt existing implementations by adding the new method
:php:`getIdentifier()`, as outlined in
:ref:`feature-107784-1760946896`.

Additionally, the possibility to dynamically add backend layout data providers
to the global :php:`DataProviderCollection` via its :php:`add()` method has been
removed. Developers should register their data providers as service definitions
in the container as described above.

Impact
======

Using the global array
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']`
to register backend layout data providers has no effect in TYPO3 v14.0 and
later.

Affected installations
======================

All installations that use
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']`
for backend layout data provider registration are affected.

This registration is typically done in an :file:`ext_localconf.php` file.

The extension scanner will report such usages.

Migration
=========

Migrate existing registrations to the new autoconfiguration-based approach.

**Before:**

..  code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    use Vendor\MyExtension\View\BackendLayout\MyLayoutDataProvider;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']['my_provider']
        = MyLayoutDataProvider::class;

**After:**

..  code-block:: php
    :caption: EXT:my_extension/Classes/View/BackendLayout/MyLayoutDataProvider.php

    use TYPO3\CMS\Backend\View\BackendLayout\DataProviderInterface;

    final class MyLayoutDataProvider implements DataProviderInterface
    {
        // ...

        public function getIdentifier(): string
        {
            return 'my_provider';
        }
    }

If you need to support multiple TYPO3 versions, you can implement both
registration methods (via :php:`$GLOBALS` and via autoconfiguration).

Ensure that :php:`getIdentifier()` is implemented, which is backward compatible
with older TYPO3 versions even if unused.

..  index:: Backend, PHP-API, FullyScanned, ext:backend
