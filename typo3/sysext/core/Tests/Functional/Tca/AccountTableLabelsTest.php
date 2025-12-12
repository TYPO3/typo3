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

namespace TYPO3\CMS\Core\Tests\Functional\Tca;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AccountTableLabelsTest extends FunctionalTestCase
{
    public static function accountTablesDataProvider(): array
    {
        return [
            'be_users' => ['be_users'],
            'be_groups' => ['be_groups'],
            'fe_users' => ['fe_users'],
            'fe_groups' => ['fe_groups'],
        ];
    }

    /**
     * A label reference to a key that does not exist renders as the reference
     * itself or as an empty string, and a reference to a key marked with
     * x-unused-since only triggers a deprecation. Both pass unnoticed in the
     * backend form, so every reference of the table is resolved against the
     * labels of its domain.
     */
    #[Test]
    #[DataProvider('accountTablesDataProvider')]
    public function allLabelReferencesResolveToNonDeprecatedLabels(string $table): void
    {
        $languageService = $this->get(LanguageServiceFactory::class)->create('default');
        $references = $this->collectLabelReferences($table);
        self::assertNotEmpty($references);
        $unresolved = [];
        foreach ($references as $reference => $usages) {
            if (!$this->isResolvable($languageService, $reference)) {
                $unresolved[] = $reference . ' (' . implode(', ', $usages) . ')';
            }
        }
        self::assertSame([], $unresolved, 'Unresolved or deprecated label references in ' . $table);
    }

    /**
     * @return array<string, list<string>> Label reference => where it is used
     */
    private function collectLabelReferences(string $table): array
    {
        $references = [];
        $schema = $this->get(TcaSchemaFactory::class)->get($table);
        $references[$schema->getTitle()][] = 'ctrl title';
        foreach ($schema->getFields() as $field) {
            $references[$field->getLabel()][] = 'column ' . $field->getName();
            foreach ($field->getConfiguration()['items'] ?? [] as $index => $item) {
                // A checkbox toggle has an item without label on purpose
                if (($item['label'] ?? '') !== '') {
                    $references[$item['label']][] = 'item ' . $index . ' of column ' . $field->getName();
                }
            }
        }
        // Tab and palette labels are not part of the schema, they only exist in the showitem strings
        $showItems = [];
        foreach ($GLOBALS['TCA'][$table]['types'] ?? [] as $type => $typeConfiguration) {
            $showItems['type ' . $type] = (string)($typeConfiguration['showitem'] ?? '');
            foreach ($typeConfiguration['columnsOverrides'] ?? [] as $column => $override) {
                if (isset($override['label'])) {
                    $references[$override['label']][] = 'columnsOverrides of column ' . $column . ' in type ' . $type;
                }
            }
        }
        foreach ($GLOBALS['TCA'][$table]['palettes'] ?? [] as $palette => $paletteConfiguration) {
            $showItems['palette ' . $palette] = (string)($paletteConfiguration['showitem'] ?? '');
            if (isset($paletteConfiguration['label'])) {
                $references[$paletteConfiguration['label']][] = 'label of palette ' . $palette;
            }
        }
        foreach ($showItems as $context => $showItem) {
            foreach (GeneralUtility::trimExplode(',', $showItem, true) as $item) {
                $parts = GeneralUtility::trimExplode(';', $item);
                if (($parts[1] ?? '') !== '') {
                    $references[$parts[1]][] = 'showitem "' . $parts[0] . '" in ' . $context;
                }
            }
        }
        return $references;
    }

    private function isResolvable(LanguageService $languageService, string $reference): bool
    {
        $reference = preg_replace('/^LLL:/', '', trim($reference));
        $separatorPosition = strrpos($reference, ':');
        if ($separatorPosition === false || str_starts_with($reference, 'EXT:') && substr_count($reference, ':') === 1) {
            return false;
        }
        $resource = substr($reference, 0, $separatorPosition);
        $key = substr($reference, $separatorPosition + 1);
        return ($languageService->getLabelsFromResource($resource)[$key] ?? '') !== '';
    }
}
