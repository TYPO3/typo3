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

namespace TYPO3\CMS\Reactions\Tests\Functional\Form\Element;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Reactions\Form\Element\FieldMapElement;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FieldMapElementTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['reactions', 'frontend'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/FieldMapElementTest_pages.csv');
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('en');
        $GLOBALS['BE_USER'] = GeneralUtility::makeInstance(BackendUserAuthentication::class);
        $GLOBALS['BE_USER']->enablecolumns = ['deleted' => true];
        $GLOBALS['BE_USER']->setBeUserByUid(1);
        $GLOBALS['BE_USER']->initializeUserSessionManager();
    }

    #[Test]
    public function renderOffersCheckboxAndRadioFields(): void
    {
        // Both store a single scalar the flat field map can carry, and a checkbox is what
        // every offered table needs to create a record that is not immediately visible.
        $html = $this->render('pages')['html'];

        self::assertStringContainsString('name="data[fields][hidden]"', $html);
        self::assertStringContainsString('name="data[fields][mount_pid_ol]"', $html);
    }

    #[Test]
    public function renderDoesNotOfferFieldsTheIntegratorMayNotEdit(): void
    {
        // The rows are rendered without the container an editing form builds them in, which
        // is where a field declaring "exclude" and one shown to admins only are taken out.
        // pages.hidden declares the first and pages.editlock the second, while pages.title
        // declares neither and stays.
        $GLOBALS['BE_USER']->setBeUserByUid(3);
        $GLOBALS['BE_USER']->fetchGroupData();

        $html = $this->render('pages')['html'];

        self::assertStringNotContainsString('name="data[fields][hidden]"', $html);
        self::assertStringNotContainsString('name="data[fields][editlock]"', $html);
        self::assertStringContainsString('name="data[fields][title]"', $html);
    }

    #[Test]
    public function renderOffersFieldsAnAdminMayEdit(): void
    {
        // The same fields for the admin the rest of these tests run as.
        $html = $this->render('pages')['html'];

        self::assertStringContainsString('name="data[fields][hidden]"', $html);
        self::assertStringContainsString('name="data[fields][editlock]"', $html);
    }

    #[Test]
    public function renderOffersLinkFieldsWithTheBrowserOfTheTargetTable(): void
    {
        // The element of a link field puts its own browser back after the field map took
        // the wizards of the target table out, and that browser is configured from the
        // field rather than from the record it belongs to.
        $html = $this->render('pages')['html'];

        self::assertStringContainsString('name="data[fields][link]"', $html);
        self::assertStringContainsString('t3js-form-field-link', $html);
    }

    #[Test]
    public function renderOffersStaticSelectFields(): void
    {
        $html = $this->render('pages')['html'];

        self::assertStringContainsString('name="data[fields][doktype]"', $html);
    }

    #[Test]
    public function renderResolvesItemsOfSelectsThatDeclareNone(): void
    {
        // tt_content.category_field is a selectSingle whose items come from an
        // itemsProcFunc and which declares no items key at all, and colPos composes its
        // items the same way. Handing the raw TCA of either to SelectSingleElement reads
        // an undefined array key, so the items have to be compiled before it is rendered.
        $html = $this->render('tt_content')['html'];

        self::assertStringContainsString('name="data[fields][category_field]"', $html);
        self::assertStringContainsString('name="data[fields][colPos]"', $html);
    }

    #[Test]
    public function renderDoesNotOfferSelectsThatSubmitAList(): void
    {
        // tsconfig_includes is a selectMultipleSideBySide, so it submits an array where the
        // flat field map holds one string per field.
        $html = $this->render('pages')['html'];

        self::assertStringNotContainsString('data[fields][tsconfig_includes]', $html);
    }

    #[Test]
    public function renderDoesNotOfferRelationalFields(): void
    {
        // fe_group and l10n_parent are selects, so a check on the type name alone would
        // offer them the moment "select" is supported.
        $html = $this->render('pages')['html'];

        self::assertStringNotContainsString('name="data[fields][fe_group]"', $html);
        self::assertStringNotContainsString('name="data[fields][l10n_parent]"', $html);
    }

    #[Test]
    public function renderLabelsFieldsRatherThanNamingThem(): void
    {
        // The label lives beside the config rather than in it, so a column handed to the
        // compiler without one comes back without one, and every row is titled with its
        // own field name.
        $html = $this->render('pages')['html'];

        self::assertStringContainsString('>Type</label>', $html);
        self::assertStringNotContainsString('>doktype</label>', $html);
        self::assertStringNotContainsString('>title</label>', $html);
    }

    #[Test]
    public function renderKeepsOfferingTheScalarFieldsItAlwaysDid(): void
    {
        $html = $this->render('pages')['html'];

        self::assertStringContainsString('name="data[fields][title]"', $html);
        self::assertStringContainsString('name="data[fields][author_email]"', $html);
        self::assertStringContainsString('name="data[fields][abstract]"', $html);
    }

    #[Test]
    public function renderOffersAFixedControlBesideThePlaceholderInput(): void
    {
        $html = $this->render('pages')['html'];

        self::assertStringContainsString('<typo3-reactions-field-map-row', $html);
        self::assertStringContainsString('data-fieldmap-mode', $html);
        self::assertStringContainsString('data-fieldmap-pane="fixed"', $html);
        self::assertStringContainsString('data-fieldmap-pane="payload"', $html);
        // The labels are addressed by translation domain, which renders the reference
        // itself where it does not resolve.
        self::assertStringContainsString('>Do not set</option>', $html);
        self::assertStringNotContainsString('reactions.db:', $html);
        // The order the enum declares is the order the select offers, so the state a row
        // starts in is the one at the top.
        self::assertMatchesRegularExpression(
            '/<option value="unset"[^>]*>.*<option value="fixed"[^>]*>.*<option value="payload"[^>]*>/s',
            $html
        );
    }

    #[Test]
    public function renderStartsInPayloadModeForAStoredPlaceholder(): void
    {
        // The mode is not stored, so it is read back off the value. Anything carrying a
        // placeholder was typed into the payload input.
        $html = $this->render('pages', ['hidden' => '${flag}'])['html'];

        self::assertStringContainsString('<typo3-reactions-field-map-row mode="payload"', $html);
        self::assertStringContainsString('value="${flag}"', $html);
    }

    #[Test]
    public function renderCarriesTheSyntaxAndASeedForThePayloadInput(): void
    {
        // Switching to the payload input leaves it empty, and an empty value is stored as
        // no mapping at all, so the row would silently do nothing. The field's own name is
        // offered as a path to edit, and the input states the syntax while it is empty.
        $html = $this->render('pages')['html'];

        self::assertStringContainsString('payload-default="${hidden}"', $html);
        self::assertStringContainsString('placeholder="${path.to.value}"', $html);
    }

    #[Test]
    public function renderStartsInPayloadModeForAValueTheControlCannotShow(): void
    {
        // Typed into the payload input without being a placeholder. A checkbox holds a
        // bitmask, so it would show this as unchecked and write 0 on the next save. A
        // select needs no such care: FormEngine adds an unknown value back as an item of
        // its own, labelled as missing, so the value survives and stays visible.
        $html = $this->render('pages', ['hidden' => 'not-a-bitmask'])['html'];

        self::assertStringContainsString('<typo3-reactions-field-map-row mode="payload"', $html);
        self::assertStringContainsString('value="not-a-bitmask"', $html);
    }

    #[Test]
    public function renderStartsInFixedModeForAStoredValue(): void
    {
        $html = $this->render('pages', ['hidden' => '1'])['html'];

        self::assertStringContainsString('<typo3-reactions-field-map-row mode="fixed"', $html);
        self::assertStringNotContainsString('mode="payload"', $html);
    }

    #[Test]
    public function renderStartsInFixedModeForAValueOnlyAnItemsProcFuncOffers(): void
    {
        // pages.backend_layout declares no item carrying -1, BackendLayoutView contributes
        // it while the form is compiled. Reading the mode off the declared items alone
        // would therefore offer a stored -1 as a placeholder rather than as a selection.
        $html = $this->render('pages', ['backend_layout' => '-1'])['html'];

        self::assertStringContainsString('<typo3-reactions-field-map-row mode="fixed"', $html);
        self::assertStringNotContainsString('mode="payload"', $html);
    }

    #[Test]
    public function renderStartsInPayloadModeForAValueTheComposedItemsDoNotOffer(): void
    {
        $html = $this->render('pages', ['backend_layout' => 'no-such-layout'])['html'];

        self::assertStringContainsString('<typo3-reactions-field-map-row mode="payload"', $html);
        self::assertStringNotContainsString('mode="fixed"', $html);
    }

    #[Test]
    public function renderRegistersItsJavaScriptModuleOnlyWhereAControlNeedsIt(): void
    {
        // The delegated controls register modules of their own, so this asserts presence
        // rather than a count.
        $names = array_map(
            static fn($module): string => (string)$module->getName(),
            $this->render('pages')['javaScriptModules']
        );

        self::assertContains('@typo3/reactions/field-map-element.js', $names);
        self::assertSame([], $this->render('not_a_table')['javaScriptModules']);
    }

    #[Test]
    public function renderNestsTheMappedFieldsInAPanelOfSections(): void
    {
        // Without it the mapped fields read as siblings of the field they belong to.
        $html = $this->render('pages')['html'];

        self::assertStringContainsString('<div class="panel panel-default">', $html);
        self::assertStringContainsString('<div class="form-section">', $html);
        // The field map does not constrain its own width any more. The controls it renders
        // bring whatever width their own element asks for, which is not this element's say.
        self::assertStringContainsString('<div class="form-control-wrap">', $html);
    }

    #[Test]
    public function renderWarnsWhenTheTableHasNoMappableFields(): void
    {
        self::assertStringContainsString('alert alert-warning', $this->render('not_a_table')['html']);
    }

    #[Test]
    public function renderSubmitsAnEmptyMapWhereNoRowSubmitsAnything(): void
    {
        // A row set to not be written disables the one input carrying the field name, so a
        // map of nothing but those would be absent from the request and DataHandler would
        // keep what was stored before. The fallback comes first, so a row that does submit
        // something replaces it.
        $html = $this->render('pages')['html'];

        self::assertStringContainsString('<input type="hidden" name="data[fields]" value="">', $html);
        self::assertLessThan(
            strpos($html, 'name="data[fields][title]"'),
            strpos($html, 'name="data[fields]" value=""')
        );
    }

    #[Test]
    public function renderSubmitsAnEmptyMapForATableOfferingNoMappableField(): void
    {
        // Without it a reaction pointed at such a table keeps the map of the table it
        // pointed at before, and every call reports those keys as ones it cannot write.
        $html = $this->render('not_a_table')['html'];

        self::assertStringContainsString('<input type="hidden" name="data[fields]" value="">', $html);
    }

    #[Test]
    public function renderWritesNothingForAMapThatWasNeverSaved(): void
    {
        // Nothing is configured yet, so no row is written. Starting on the control instead
        // would put the default of the table into the map the moment the reaction is saved,
        // and every call would write it from then on.
        $html = $this->render('pages')['html'];

        self::assertStringContainsString('<typo3-reactions-field-map-row mode="unset"', $html);
        self::assertStringNotContainsString('mode="fixed"', $html);
        self::assertStringNotContainsString('mode="payload"', $html);
    }

    #[Test]
    public function renderDisablesAFieldTheSavedMapLeavesOut(): void
    {
        // A key missing from a map that has been saved is a field the integrator set to not
        // be written. The control stays visible so the row keeps its label, and is disabled
        // so the field reads as inactive rather than as one holding the shown value.
        $html = $this->render('pages', ['title' => 'Test'])['html'];

        self::assertStringContainsString('<typo3-reactions-field-map-row mode="unset"', $html);
        self::assertMatchesRegularExpression('/name="data\[fields\]\[doktype\]"\s+value="1" disabled>/', $html);
    }

    #[Test]
    public function renderReadsAnEmptyStoredValueAsAFieldThatIsNotWritten(): void
    {
        // An empty value is what the reaction skips, so a map carrying one says the same
        // thing as a map carrying no key at all. Reading it as a fixed value instead would
        // show the default of the table as the one being written, and turn it into a value
        // that really is written on the next save.
        $html = $this->render('pages', ['doktype' => '', 'title' => 'Test'])['html'];

        // The empty row is not written and shows the default of the table disabled, while
        // the row beside it carries a value and is written.
        self::assertMatchesRegularExpression('/name="data\[fields\]\[doktype\]"\s+value="1" disabled>/', $html);
        self::assertMatchesRegularExpression('/name="data\[fields\]\[title\]"\s+value="Test">/', $html);
    }

    #[Test]
    public function renderShowsWhatTheTargetTableAppliesWhereNothingIsConfigured(): void
    {
        // pages.doktype defaults to 1, so an unconfigured row shows that rather than the
        // empty state a control falls back to, which for a select is its first item. The
        // compiled row holds a select value as a list, so casting it would read "Array".
        $html = $this->render('pages')['html'];

        self::assertMatchesRegularExpression('/name="data\[fields\]\[doktype\]"\s+value="1" disabled>/', $html);
        self::assertStringNotContainsString('value="Array"', $html);
    }

    #[Test]
    public function renderNamesOneControlPerFieldOnly(): void
    {
        // Two controls under one name answer the form's named getter with a RadioNodeList,
        // which is what the checkbox element writes its bitmask through, and writing to that
        // list does nothing. The editors are therefore named outside the data array, and the
        // one input carrying the field name is what the field map is built from.
        $html = $this->render('pages')['html'];

        self::assertSame(1, substr_count($html, 'name="data[fields][hidden]"'));
        self::assertStringContainsString('name="fieldMapFixed[hidden]"', $html);
        self::assertStringContainsString('name="fieldMapPayload[hidden]"', $html);
        self::assertStringContainsString('data-fieldmap-source="fieldMapFixed[hidden]"', $html);
    }

    #[Test]
    public function renderHidesTheEditorThatIsNotInUse(): void
    {
        // Both editors visible at once is what the row would show until the module upgrades.
        $fixed = $this->render('pages')['html'];
        $payload = $this->render('pages', ['hidden' => '${flag}'])['html'];

        self::assertStringContainsString('data-fieldmap-pane="payload" data-fieldmap-source="fieldMapPayload[hidden]" hidden', $fixed);
        self::assertStringNotContainsString('data-fieldmap-pane="fixed" data-fieldmap-source="fieldMapFixed[hidden]" hidden', $fixed);
        self::assertStringContainsString('data-fieldmap-pane="fixed" data-fieldmap-source="fieldMapFixed[hidden]" hidden', $payload);
    }

    #[Test]
    public function renderKeepsAStoredValueWithoutAStringFormOutOfTheDataProviders(): void
    {
        // The map is stored as JSON and the record may be written by hand, so a value may
        // decode to anything. Handed to the compiler as the row, a datetime column answers
        // an array with a TypeError, and the record could then not be opened to repair it.
        $html = $this->render('pages', ['lastUpdated' => ['nested' => 1], 'hidden' => ['x']])['html'];

        self::assertStringContainsString('name="data[fields][lastUpdated]"', $html);
    }

    #[Test]
    public function renderResolvesItemsForThePageTheRecordsAreCreatedOn(): void
    {
        // The reaction record itself sits on the root, sys_reaction being a rootLevel table,
        // so the page TSconfig of the storage page is the only one that says anything about
        // the records this reaction writes.
        $html = $this->render('pages', [], 1)['html'];

        self::assertStringNotContainsString('value="254"', $html);
        self::assertStringContainsString('value="254"', $this->render('pages', [], 2)['html']);
    }

    #[Test]
    public function renderNamesTheColumnBesideAnInvertedLabel(): void
    {
        // A checkbox declaring invertStateDisplay is labelled for the state it shows, while
        // the payload input writes the value as stored, so the two would read as opposites.
        $html = $this->render('pages', ['hidden' => '${flag}'])['html'];

        self::assertStringContainsString('<code>[hidden]</code>', $html);
        self::assertStringContainsString('displayed inverted', $html);
    }

    #[Test]
    public function renderShowsWhatADateTimeColumnWouldDefaultTo(): void
    {
        // The data providers turn a datetime column into a \DateTimeImmutable, so the row
        // has to read the default as an object where every other type hands it a scalar.
        $GLOBALS['TCA']['pages']['columns']['starttime']['config']['default'] = 1700000000;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $html = $this->render('pages')['html'];

        self::assertStringContainsString('name="fieldMapFixed[starttime]" value="2023-11-14T22:13:20"', $html);
    }

    private function render(string $table, array $itemValue = [], int $storagePid = 0): array
    {
        $config = ['type' => 'json', 'renderType' => 'fieldMap'];
        $node = $this->get(FieldMapElement::class);
        $node->setData([
            'request' => new ServerRequest('http://localhost/typo3/', 'GET'),
            'effectivePid' => 0,
            'inlineFirstPid' => 0,
            'tcaSchemata' => $this->get(TcaSchemaFactory::class),
            'fullTca' => $GLOBALS['TCA'],
            'selectTreeCompileItems' => false,
            'tableName' => 'sys_reaction',
            'fieldName' => 'fields',
            'databaseRow' => [
                'uid' => 1,
                'table_name' => [$table],
                'storage_pid' => $storagePid === 0 ? [] : [['table' => 'pages', 'uid' => $storagePid]],
            ],
            'processedTca' => [
                'columns' => [
                    'fields' => ['config' => $config],
                ],
            ],
            'parameterArray' => [
                'itemFormElName' => 'data[fields]',
                'itemFormElValue' => $itemValue,
                'fieldConf' => [
                    'label' => 'Fields',
                    'config' => $config,
                ],
            ],
        ]);

        return $node->render();
    }
}
