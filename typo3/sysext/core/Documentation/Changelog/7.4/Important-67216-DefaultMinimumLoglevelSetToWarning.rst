
.. include:: ../../Includes.txt

===============================================================
Important: #67216 - Default minimum log level is set to warning
===============================================================

See :issue:`67216`

Description
===========

The minimum logging severity for TYPO3 has been raised from DEBUG to WARNING in order to ship strong defaults for production.
Log messages of the severities DEBUG, NOTICE and INFO will be suppressed in the default setup.

The previous behavior from TYPO3 <= 7.3 can be achieved with the following configuration:

.. code-block:: php

        $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'] = array(
                \TYPO3\CMS\Core\Log\LogLevel::DEBUG => array(
                        \TYPO3\CMS\Core\Log\Writer\FileWriter::class => array(
                                'logFile' => 'typo3temp/logs/typo3.log'
                        ),
                ),
        );
