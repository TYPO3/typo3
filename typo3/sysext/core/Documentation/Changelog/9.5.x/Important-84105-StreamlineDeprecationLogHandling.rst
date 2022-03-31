.. include:: /Includes.rst.txt

=======================================================
Important: #84105 - Streamline deprecation log handling
=======================================================

See :issue:`84105`

Description
===========

TYPO3 now comes with default configuration, in which deprecation logging is disabled.
This means if you update to the latest TYPO3 version, you need to change your development
configuration to enable deprecation logging in case you need it.

Enabling the deprecation log can be done in Install Tool. Navigate to "Settings",
click on "Choose Preset" in "Configuration Presets" pane, open "Debug Settings", select "Debug"
and submit with "Activate preset".
Disabling deprecation log can be done by selecting the "Live" preset instead.

Please note, that these steps only enable/disable the FileWriter, which comes with TYPO3 default configuration.
If you manually configured **additional** writers for the `TYPO3.CMS.deprecations` logger, you need to manually remove
them to completely disable deprecation logging.

This is how the LOG section in :file:`LocalConfiguration.php` looks like with disabled deprecation logging:

.. code-block:: php

   'LOG' => [
       'TYPO3' => [
           'CMS' => [
               'deprecations' => [
                   'writerConfiguration' => [
                       \TYPO3\CMS\Core\Log\LogLevel::NOTICE => [
                           \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                               'disabled' => true,
                           ],
                       ],
                   ],
               ],
           ],
       ],
   ],

Any other log writer can be disabled as well, by providing a `disabled` option with a truthy value.

.. index:: LocalConfiguration, ext:core
