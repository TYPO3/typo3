<?php
return [
    'TYPO3\\CMS\\Lowlevel\\Command\\WorkspaceVersionRecordsCommand' => \TYPO3\CMS\Workspaces\Command\WorkspaceVersionRecordsCommand::class,
    'TYPO3\\CMS\\Version\\DataHandler\\CommandMap' => \TYPO3\CMS\Workspaces\DataHandler\CommandMap::class,
    'TYPO3\\CMS\\Version\\Dependency\\DependencyEntityFactory' => \TYPO3\CMS\Workspaces\Dependency\DependencyEntityFactory::class,
    'TYPO3\\CMS\\Version\\Dependency\\DependencyResolver' => \TYPO3\CMS\Workspaces\Dependency\DependencyResolver::class,
    'TYPO3\\CMS\\Version\\Dependency\\ElementEntity' => \TYPO3\CMS\Workspaces\Dependency\ElementEntity::class,
    'TYPO3\\CMS\\Version\\Dependency\\ElementEntityProcessor' => \TYPO3\CMS\Workspaces\Dependency\ElementEntityProcessor::class,
    'TYPO3\\CMS\\Version\\Dependency\\EventCallback' => \TYPO3\CMS\Workspaces\Dependency\EventCallback::class,
    'TYPO3\\CMS\\Version\\Dependency\\ReferenceEntity' => \TYPO3\CMS\Workspaces\Dependency\ReferenceEntity::class,
    'TYPO3\\CMS\\Version\\Hook\\DataHandlerHook' => \TYPO3\CMS\Workspaces\Hook\DataHandlerHook::class,
    'TYPO3\\CMS\\Version\\Hook\\PreviewHook' => \TYPO3\CMS\Workspaces\Preview\PreviewUriBuilder::class,
    'TYPO3\\CMS\\Version\\Task\\AutoPublishTask' => \TYPO3\CMS\Workspaces\Task\AutoPublishTask::class,
    'TYPO3\\CMS\\Version\\Utility\\WorkspacesUtility' => \TYPO3\CMS\Workspaces\Service\WorkspaceService::class,
];
