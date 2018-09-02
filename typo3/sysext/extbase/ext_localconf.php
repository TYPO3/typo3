<?php
defined('TYPO3_MODE') or die();

// We set the default implementation for Storage Backend & Query Settings in Backend and Frontend.
// The code below is NO PUBLIC API!
/** @var \TYPO3\CMS\Extbase\Object\Container\Container $extbaseObjectContainer */
$extbaseObjectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class);
// Singleton
$extbaseObjectContainer->registerImplementation(\TYPO3\CMS\Extbase\Persistence\QueryInterface::class, \TYPO3\CMS\Extbase\Persistence\Generic\Query::class);
$extbaseObjectContainer->registerImplementation(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface::class, \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult::class);
$extbaseObjectContainer->registerImplementation(\TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface::class, \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
$extbaseObjectContainer->registerImplementation(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\BackendInterface::class, \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend::class);
$extbaseObjectContainer->registerImplementation(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::class, \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class);
unset($extbaseObjectContainer);

// Register type converters
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\TYPO3\CMS\Extbase\Property\TypeConverter\ArrayConverter::class);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\TYPO3\CMS\Extbase\Property\TypeConverter\BooleanConverter::class);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter::class);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\TYPO3\CMS\Extbase\Property\TypeConverter\FloatConverter::class);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\TYPO3\CMS\Extbase\Property\TypeConverter\IntegerConverter::class);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\TYPO3\CMS\Extbase\Property\TypeConverter\ObjectStorageConverter::class);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter::class);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\TYPO3\CMS\Extbase\Property\TypeConverter\ObjectConverter::class);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\TYPO3\CMS\Extbase\Property\TypeConverter\StringConverter::class);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\TYPO3\CMS\Extbase\Property\TypeConverter\CoreTypeConverter::class);
// Experimental FAL<->extbase converters
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\TYPO3\CMS\Extbase\Property\TypeConverter\FileConverter::class);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\TYPO3\CMS\Extbase\Property\TypeConverter\FileReferenceConverter::class);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\TYPO3\CMS\Extbase\Property\TypeConverter\FolderBasedFileCollectionConverter::class);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\TYPO3\CMS\Extbase\Property\TypeConverter\StaticFileCollectionConverter::class);
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\TYPO3\CMS\Extbase\Property\TypeConverter\FolderConverter::class);

// register help command
// @deprecated will be removed in TYPO3 v10.0. Use symfony/console commands instead
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = \TYPO3\CMS\Extbase\Command\HelpCommandController::class;

// @deprecated will be removed in TYPO3 v10.0. Use symfony/console commands instead
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Extbase\Scheduler\Task::class] = [
    'extension' => 'extbase',
    'title' => 'LLL:EXT:extbase/Resources/Private/Language/locallang_db.xlf:task.name',
    'description' => 'LLL:EXT:extbase/Resources/Private/Language/locallang_db.xlf:task.description',
    'additionalFields' => \TYPO3\CMS\Extbase\Scheduler\FieldProvider::class
];

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['checkFlexFormValue'][] = \TYPO3\CMS\Extbase\Hook\DataHandler\CheckFlexFormValue::class;
