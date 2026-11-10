<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Form\Upgrades;

use Doctrine\DBAL\Result;
use TYPO3\CMS\Core\Attribute\UpgradeWizard;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Upgrades\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Core\Upgrades\RepeatableInterface;
use TYPO3\CMS\Core\Upgrades\UpgradeWizardInterface;
use TYPO3\CMS\Form\Storage\JsonObjectKeyOrderPreserver;

/**
 * Restore the technical value of finisher option "translation.language".
 *
 * FlexForm finisher overrides and the form editor used to translate select item values,
 * so the item value "default" was stored as its backend label, e.g. "Default" or "Standard".
 * Every non-empty value which is not a valid language key is replaced by "default". Such
 * values already render emails in the default language, so emails do not change. The wizard
 * is repeatable, so forms migrated to the database later on can be migrated as well.
 *
 * @since 14.3
 * @internal
 */
#[UpgradeWizard('formFinisherTranslationLanguageMigration')]
final readonly class FinisherTranslationLanguageUpgradeWizard implements UpgradeWizardInterface, RepeatableInterface
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private Locales $locales,
        private JsonObjectKeyOrderPreserver $jsonObjectKeyOrderPreserver,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate translated "translation.language" values of form email finishers';
    }

    public function getDescription(): string
    {
        return 'Form definitions saved in the form editor and form plugins with "Override finisher settings" '
            . 'may store the translated label of the language option "default" (e.g. "Default" or "Standard") '
            . 'instead of the value "default". This wizard replaces every value which is not a valid language '
            . 'key by "default" in form definitions stored in the database (form_definition) and in form '
            . 'plugins (tt_content). This also applies to custom finishers using the option '
            . '"translation.language". Run it again after migrating file-based forms to the database. '
            . 'Emails of migrated forms keep using the default language. Select "Auto" '
            . 'to use the frontend language instead.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        $result = $this->getRecords();
        while ($record = $result->fetchAssociative()) {
            if ($this->migrateFlexForm((string)$record['pi_flexform']) !== null) {
                return true;
            }
        }
        $result = $this->getFormDefinitionRecords();
        while ($record = $result->fetchAssociative()) {
            if ($this->migrateFormDefinition((string)$record['configuration']) !== null) {
                return true;
            }
        }
        return false;
    }

    public function executeUpdate(): bool
    {
        $connection = $this->connectionPool->getConnectionForTable('tt_content');
        $result = $this->getRecords();
        while ($record = $result->fetchAssociative()) {
            $migratedFlexForm = $this->migrateFlexForm((string)$record['pi_flexform']);
            if ($migratedFlexForm !== null) {
                $connection->update(
                    'tt_content',
                    ['pi_flexform' => $migratedFlexForm],
                    ['uid' => (int)$record['uid']]
                );
            }
        }
        $connection = $this->connectionPool->getConnectionForTable('form_definition');
        $result = $this->getFormDefinitionRecords();
        while ($record = $result->fetchAssociative()) {
            $migratedConfiguration = $this->migrateFormDefinition((string)$record['configuration']);
            if ($migratedConfiguration !== null) {
                $connection->update(
                    'form_definition',
                    ['configuration' => $migratedConfiguration],
                    ['uid' => (int)$record['uid']]
                );
            }
        }
        return true;
    }

    private function getRecords(): Result
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        return $queryBuilder
            ->select('uid', 'pi_flexform')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('form_formframework')),
                $queryBuilder->expr()->like(
                    'pi_flexform',
                    $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards('.translation.language') . '%')
                )
            )
            ->executeQuery();
    }

    private function getFormDefinitionRecords(): Result
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('form_definition');
        $queryBuilder->getRestrictions()->removeAll()->add(new DeletedRestriction());
        return $queryBuilder
            ->select('uid', 'configuration')
            ->from('form_definition')
            ->where(
                $queryBuilder->expr()->like(
                    'configuration',
                    $queryBuilder->createNamedParameter('%' . $queryBuilder->escapeLikeWildcards('"translation"') . '%')
                )
            )
            ->executeQuery();
    }

    /**
     * Non-empty values which are no valid language key, e.g. a translated label of the value "default".
     */
    private function isInvalidLanguage(mixed $language): bool
    {
        return is_string($language) && $language !== '' && !$this->locales->isValidLanguageKey($language);
    }

    /**
     * @return string|null The migrated FlexForm XML, or null if nothing has to be migrated
     */
    private function migrateFlexForm(string $flexFormXml): ?string
    {
        if ($flexFormXml === '') {
            return null;
        }
        $document = new \DOMDocument();
        $previousErrorHandling = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($flexFormXml);
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorHandling);
        if (!$loaded) {
            return null;
        }

        $modified = false;
        $fields = (new \DOMXPath($document))->query('//field[starts-with(@index, "settings.finishers.")]');
        foreach ($fields ?: [] as $field) {
            if (!$field instanceof \DOMElement
                || preg_match('/^settings\.finishers\.[^.]+\.translation\.language$/', $field->getAttribute('index')) !== 1
            ) {
                continue;
            }
            foreach ($field->getElementsByTagName('value') as $value) {
                if ($value->getAttribute('index') === 'vDEF' && $this->isInvalidLanguage($value->nodeValue)) {
                    $value->nodeValue = 'default';
                    $modified = true;
                }
            }
        }

        return $modified ? (string)$document->saveXML() : null;
    }

    /**
     * @return array|null The migrated form definition, or null if nothing has to be migrated
     */
    private function migrateFormDefinition(string $configuration): ?array
    {
        if ($configuration === '') {
            return null;
        }
        try {
            $formDefinition = json_decode($configuration, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }
        if (!is_array($formDefinition) || !is_array($formDefinition['finishers'] ?? null)) {
            return null;
        }

        $formDefinition = $this->jsonObjectKeyOrderPreserver->restore($formDefinition);
        $modified = false;
        foreach ($formDefinition['finishers'] as $index => $finisher) {
            if ($this->isInvalidLanguage($finisher['options']['translation']['language'] ?? null)) {
                $formDefinition['finishers'][$index]['options']['translation']['language'] = 'default';
                $modified = true;
            }
        }

        return $modified ? $this->jsonObjectKeyOrderPreserver->protect($formDefinition) : null;
    }
}
