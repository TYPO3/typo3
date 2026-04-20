<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Package;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Package\Event\PackageInitializationEvent;
use TYPO3\CMS\Core\Package\Initialization\CheckForImportRequirements;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * Only for use in extension:setup and TYPO3 installation
 * The class can not be final, because it is mocked in tests
 * @internal
 */
#[Autoconfigure(public: true)]
readonly class PackageSetup
{
    public function __construct(
        private SqlReader $sqlReader,
        private SchemaMigrator $schemaMigrator,
        private ExtensionConfiguration $extensionConfiguration,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * @return FlashMessage[]
     */
    public function setup(array $packagesToSetUp, bool $packageActivated = false, ?object $emitter = null, ?ContainerInterface $container = null): array
    {
        $messages = [];
        $this->updateDatabaseSchemaForAllPackages();
        foreach ($packagesToSetUp as $packageKey => $package) {
            $this->extensionConfiguration->synchronizeExtConfTemplateWithLocalConfiguration($packageKey);
            $event = $this->eventDispatcher->dispatch(
                new PackageInitializationEvent(
                    extensionKey: $packageKey,
                    package: $package,
                    packageActivated: $packageActivated,
                    container: $container,
                    emitter: $emitter,
                ),
            );
            if ($event->hasStorageEntry(CheckForImportRequirements::class)) {
                $messages[] = new FlashMessage(
                    $event->getStorageEntry(CheckForImportRequirements::class)->getResult()['exception']?->getMessage() ?? '',
                    '',
                    ContextualFeedbackSeverity::WARNING
                );
            }
        }
        return $messages;
    }

    private function updateDatabaseSchemaForAllPackages(): void
    {
        $sqlStatements = [];
        $sqlStatements[] = $this->sqlReader->getTablesDefinitionString();
        $sqlStatements = $this->sqlReader->getCreateTableStatementArray(implode(LF . LF, array_filter($sqlStatements)));
        $updateStatements = $this->schemaMigrator->getUpdateSuggestions($sqlStatements);
        $updateStatements = array_merge_recursive(...array_values($updateStatements));
        $selectedStatements = [];
        foreach (['add', 'change', 'create_table', 'change_table'] as $action) {
            if (empty($updateStatements[$action])) {
                continue;
            }
            $statements = array_combine(array_keys($updateStatements[$action]), array_fill(0, count($updateStatements[$action]), true));
            $selectedStatements = array_merge(
                $selectedStatements,
                $statements
            );
        }
        $this->schemaMigrator->migrate($sqlStatements, $selectedStatements);
    }
}
