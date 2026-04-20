..  include:: /Includes.rst.txt

..  _deprecation-109295-1742407200:

==================================================================
Deprecation: #109295 - DatabaseWriter::setLogTable()/getLogTable()
==================================================================

See :issue:`109295`

Description
===========

The methods :php:`setLogTable()` and :php:`getLogTable()` in
:php:`\TYPO3\CMS\Core\Log\Writer\DatabaseWriter` have been deprecated.

:php-short:`\TYPO3\CMS\Core\Log\Writer\DatabaseWriter` is a dedicated writer
for the :sql:`sys_log` table. Its :php:`writeLog()` method maps
:php-short:`\TYPO3\CMS\Core\Log\LogRecord` fields to the :sql:`sys_log`
schema (:sql:`request_id`, :sql:`time_micro`, :sql:`component`,
:sql:`level`, :sql:`message`, :sql:`data`, :sql:`tstamp`). Allowing an
arbitrary table to be set via :php:`setLogTable()` created a false sense of
flexibility - any custom table needs to replicate the full
:sql:`sys_log` schema to work correctly.

The long-term goal is to make
:php-short:`\TYPO3\CMS\Core\Log\Writer\DatabaseWriter` :php:`final` and to
remove the :php:`$logTable` property entirely.

Impact
======

Calling :php:`setLogTable()` or :php:`getLogTable()` triggers a PHP
:php:`E_USER_DEPRECATED` error. This also includes passing
:php:`logTable` as a configuration option when
:php-short:`\TYPO3\CMS\Core\Log\Writer\DatabaseWriter` is registered via
:php:`$GLOBALS['TYPO3_CONF_VARS']['LOG']`, since the
:php:`AbstractWriter` constructor resolves options to :php:`set*()` calls.

Support will be removed in TYPO3 v15.0.

Affected installations
======================

Installations that configure
:php-short:`\TYPO3\CMS\Core\Log\Writer\DatabaseWriter` with a custom
:php:`logTable` option, or that call :php:`setLogTable()` or
:php:`getLogTable()` on a
:php-short:`\TYPO3\CMS\Core\Log\Writer\DatabaseWriter` instance.

The extension scanner detects direct calls to :php:`->setLogTable()` and
:php:`->getLogTable()`. The more common case, passing :php:`logTable` as a
configuration option via :php:`$GLOBALS['TYPO3_CONF_VARS']['LOG']`, cannot
be detected automatically and requires a manual search for
:php-short:`\TYPO3\CMS\Core\Log\Writer\DatabaseWriter` usage with a
:php:`logTable` key.

Migration
=========

Replace :php-short:`\TYPO3\CMS\Core\Log\Writer\DatabaseWriter` with a
dedicated writer that extends
:php:`\TYPO3\CMS\Core\Log\Writer\AbstractWriter` and implements
:php:`writeLog()` with explicit field mapping for the custom table.

Before:

..  code-block:: php

    use Psr\Log\LogLevel;
    use TYPO3\CMS\Core\Log\Writer\DatabaseWriter;

    $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][LogLevel::WARNING] =
    [
        DatabaseWriter::class => ['logTable' => 'my_custom_log'],
    ];

After:

..  code-block:: php
    :caption: EXT:my_extension/Classes/Log/Writer/MyCustomTableWriter.php

    namespace MyVendor\MyExtension\Log\Writer;

    use TYPO3\CMS\Core\Database\ConnectionPool;
    use TYPO3\CMS\Core\Log\LogRecord;
    use TYPO3\CMS\Core\Log\Writer\AbstractWriter;
    use TYPO3\CMS\Core\Log\Writer\WriterInterface;
    use TYPO3\CMS\Core\Utility\GeneralUtility;

    class MyCustomTableWriter extends AbstractWriter
    {
        public function writeLog(LogRecord $record): WriterInterface
        {
            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('my_custom_log')
                ->insert('my_custom_log', [
                    'created' => (int)$record->getCreated(),
                    'level' => $record->getLevel(),
                    'message' => $record->getMessage(),
                ]);
            return $this;
        }
    }

..  code-block:: php

    use Psr\Log\LogLevel;
    use MyVendor\MyExtension\Log\Writer\MyCustomTableWriter;

    $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][LogLevel::WARNING] = [
        MyCustomTableWriter::class => [],
    ];

..  index:: PHP-API, PartiallyScanned, ext:core
