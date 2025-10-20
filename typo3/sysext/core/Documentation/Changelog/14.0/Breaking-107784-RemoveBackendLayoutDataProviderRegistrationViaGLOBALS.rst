..  include:: /Includes.rst.txt

..  _breaking-107784-1760947244:

========================================================================================
Breaking: #107784 - Remove backend layout data provider registration via :php:`$GLOBALS`
========================================================================================

See :issue:`107784`

Description
===========

The possibility to register backend layout data provider via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']`
has been replaced by autoconfiguration via the :yaml:`page_layout.data_provider`
service tag. The tag is automatically added based on the implemented
:php:`DataProviderInterface`. However, manual configuration in the
:file:`Services.yaml` is still possible, especially in case no
autoconfiguration is enabled. Developers however need to adapt existing
implementations by adding the new method :php:`getIdentifier()`, as
outlined in the appropriate :ref:`feature description <feature-107784-1760946896>`.

In addition, the possibility to dynamically add backend layout data providers to
the global :php:`DataProviderCollection` by using their :php:`add()` method has
been removed. Developers are advised to make data providers available in the
service container as described above.


Impact
======

Utilizing the :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']`
array won't have any effect anymore in TYPO3 v14.0+.


Affected installations
======================

All installations where :php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['BackendLayoutDataProvider']`
is used for backend layout data provider registration are affected. This
registration is normally done in an :file:`ext_localconf.php` file. The
extension scanner will report any usages.


Migration
=========

Migrate existing registrations to the new autoconfigured-based approach.

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

If you need to support multiple TYPO3 Core versions at once, make sure to
implement both registration methods (via :php:`$GLOBALS` and using
autoconfiguration). Make sure to implement the new method
:php:`getIdentifier()`, which is backwards compatible (even if not used in
older TYPO3 versions).

..  index:: Backend, PHP-API, FullyScanned, ext:backend
