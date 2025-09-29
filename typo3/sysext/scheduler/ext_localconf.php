<?php

declare(strict_types=1);

use TYPO3\CMS\Scheduler\Form\Element\AdditionalSchedulerFieldsElement;
use TYPO3\CMS\Scheduler\Form\Element\RegisteredExtractors;
use TYPO3\CMS\Scheduler\Form\Element\TaskTypeInfoElement;
use TYPO3\CMS\Scheduler\Form\Element\TimingOptionsElement;
use TYPO3\CMS\Scheduler\Form\FieldInformation\ExpirePeriodInformation;
use TYPO3\CMS\Scheduler\Hooks\SchedulerTaskPersistenceValidator;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

defined('TYPO3') or die();

// Add execute schedulable command task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][ExecuteSchedulableCommandTask::class] = [
    'extension' => 'scheduler',
    'title' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:executeSchedulableCommandTask.name',
    'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:executeSchedulableCommandTask.name',
    'additionalFields' => ExecuteSchedulableCommandAdditionalFieldProvider::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1746714036] = [
    'nodeName' => 'schedulerTimingOptions',
    'priority' => 40,
    'class' => TimingOptionsElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1746714037] = [
    'nodeName' => 'schedulerAdditionalFields',
    'priority' => 40,
    'class' => AdditionalSchedulerFieldsElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1758189546] = [
    'nodeName' => 'taskTypeInfo',
    'priority' => 40,
    'class' => TaskTypeInfoElement::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1758791054] = [
    'nodeName' => 'registeredExtractors',
    'priority' => 40,
    'class' => RegisteredExtractors::class,
];

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1758906785] = [
    'nodeName' => 'expirePeriodInformation',
    'priority' => 40,
    'class' => ExpirePeriodInformation::class,
];

// Register hook for datamap
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = SchedulerTaskPersistenceValidator::class;
