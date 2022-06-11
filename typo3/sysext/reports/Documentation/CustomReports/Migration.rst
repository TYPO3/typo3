.. include:: /Includes.rst.txt

.. _migration:

=========
Migration
=========

By implementing the required methods of the interfaces, the custom reports
are fully backwards compatible.

.. note::
    Additional methods have been added to the interfaces
    :php:`TYPO3\CMS\Reports\ReportInterface` and
    :php:`TYPO3\CMS\Reports\StatusProviderInterface` with version 12.0.

If TYPO3 v12+ is the only supported version, the configuration
:php:`$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']` from the
:file:`ext_localconf.php` file can be removed. If you need to support
version 11 you can leave the configurations in the file. They are not
evaluated anymore in version 12.

Report
=========

If :yaml:`autoconfigure` is not enabled in your :file:`Configuration/Services.(yaml|php)`,
add the tag :yaml:`reports.report` manually to your `reports` service.

.. include:: /CodeSnippets/Manual/RegisterReport.rst.txt

The old registration can be removed, if support for TYPO3 v11 or lower is not
necessary.

.. code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    // Before in ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['extension']['general'] = [
        'title' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:title',
        'description' => 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:description',
        'icon' => 'EXT:my_extension/Resources/Public/Icons/Extension.svg',
        'report' => \Vendor\MyExtension\Report\MyReport::class
    ];

Additionally, make sure to implement all methods of
:php:`TYPO3\CMS\Reports\ReportInterface`.

.. code-block:: php
    :caption: EXT:my_extension/Classes/Report/MyReport.php

    use TYPO3\CMS\Reports\ReportInterface;

    class MyReport implements ReportInterface
    {
        // ...

        // Implement additional methods from ReportInterface

        public function getReport(): string
        {
            return 'Full report';
        }

        public function getIdentifier(): string
        {
            return 'general';
        }

        public function getTitle(): string
        {
            return 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:title';
        }

        public function getDescription(): string
        {
            return 'LLL:EXT:my_extension/Resources/Private/Language/locallang.xlf:description';
        }

        public function getIconIdentifier(): string
        {
            return 'module-reports';
        }
    }

Refer to the :ref:`Icon API <t3coreapi:icon>` on how to register the icon.

Status
=========

If :yaml:`autoconfigure` is not enabled in your :file:`Configuration/Services.(yaml|php)`,
add the tag :yaml:`reports.status` manually to your `status` service.

.. include:: /CodeSnippets/Manual/RegisterStatus.rst.txt

The old registration can be removed, if support for TYPO3 v11 or lower is not
necessary.

.. code-block:: php
    :caption: EXT:my_extension/ext_localconf.php

    // Before in ext_localconf.php

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']['providers']['label'] = [
        \Vendor\MyExtension\Status\MyStatus::class,
    ];

Additionally, make sure to implement all methods of
:php:`TYPO3\CMS\Reports\StatusProviderInterface`.

.. code-block:: php
    :caption: EXT:my_extension/Classes/Status/MyStatus.php

    use TYPO3\CMS\Reports\StatusProviderInterface

    class MyStatus implements StatusProviderInterface
    {
        // ...

        // Implement additional methods from StatusProviderInterface

        public function getStatus(): array
        {
            return [];
        }

        public function getLabel(): string
        {
            return 'label';
        }
    }
