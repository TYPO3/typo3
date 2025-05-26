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

namespace TYPO3Tests\TestBolt\EventListener;

use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchema;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Event\AfterTemplatesHaveBeenDeterminedEvent;

final class AddTypoScriptFromSiteExtensionEventListener
{
    // 2024-04-07T13:58:03+00:00 (UTC/GMT)
    private const SIMULATED_TIME = 1712498283;
    private ?TcaSchema $schema;

    public function __construct(TcaSchemaFactory $tcaSchemaFactory)
    {
        $this->schema = $tcaSchemaFactory->has('sys_template') ? $tcaSchemaFactory->get('sys_template') : null;
    }

    public function __invoke(AfterTemplatesHaveBeenDeterminedEvent $event): void
    {
        $site = $event->getSite();
        if (!$site instanceof Site) {
            return;
        }
        if (!$this->schema) {
            return;
        }

        $emulateBolt = (bool)($site->getConfiguration()['test_bolt_enabled'] ?? false);
        if (!$emulateBolt) {
            return;
        }
        $constants = (string)($site->getConfiguration()['test_bolt_constants'] ?? '');
        $setup = (string)($site->getConfiguration()['test_bolt_setup'] ?? '');

        $siteRootPageId = $site->getRootPageId();
        $rootline = $event->getRootline();
        $sysTemplateRows = $event->getTemplateRows();

        $highestUid = 1;
        foreach ($sysTemplateRows as $sysTemplateRow) {
            if ((int)($sysTemplateRow['uid'] ?? 0) > $highestUid) {
                $highestUid = (int)$sysTemplateRow['uid'];
            }
        }

        $fakeRow = [
            'uid' => $highestUid + 1,
            'pid' => $siteRootPageId,
            'title' => 'Site extension include by test_bolt',
            'root' => 1,
            'clear' => 3,
            'include_static_file' => null,
            'constants' => $constants,
            'config' => $setup,
        ];
        // Set various "db" fields conditionally to be as robust as possible in case
        // core or some other loaded extension fiddles with them.
        if ($this->schema->hasCapability(TcaSchemaCapability::SoftDelete)) {
            $fakeRow[$this->schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName()] = 0;
        }
        if ($this->schema->hasCapability(TcaSchemaCapability::RestrictionDisabledField)) {
            $fakeRow[$this->schema->getCapability(TcaSchemaCapability::RestrictionDisabledField)->getFieldName()] = 0;
        }
        if ($this->schema->hasCapability(TcaSchemaCapability::RestrictionStartTime)) {
            $fakeRow[$this->schema->getCapability(TcaSchemaCapability::RestrictionStartTime)->getFieldName()] = 0;
        }
        if ($this->schema->hasCapability(TcaSchemaCapability::RestrictionEndTime)) {
            $fakeRow[$this->schema->getCapability(TcaSchemaCapability::RestrictionEndTime)->getFieldName()] = 0;
        }
        if ($this->schema->hasCapability(TcaSchemaCapability::SortByField)) {
            $fakeRow[$this->schema->getCapability(TcaSchemaCapability::SortByField)->getFieldName()] = 0;
        }
        if ($this->schema->hasCapability(TcaSchemaCapability::UpdatedAt)) {
            $fakeRow[$this->schema->getCapability(TcaSchemaCapability::UpdatedAt)->getFieldName()] = self::SIMULATED_TIME;
        }
        if ($this->schema->hasField('basedOn')) {
            $fakeRow['basedOn'] = null;
        }
        if ($this->schema->hasField('includeStaticAfterBasedOn')) {
            $fakeRow['includeStaticAfterBasedOn'] = 0;
        }
        if ($this->schema->hasField('static_file_mode')) {
            $fakeRow['static_file_mode'] = 0;
        }

        if (empty($sysTemplateRows)) {
            // Simple things first: If there are no sys_template records yet, add our fake row and done.
            $sysTemplateRows[] = $fakeRow;
            $event->setTemplateRows($sysTemplateRows);
            return;
        }

        // When there are existing sys_template rows, we try to add our fake row at the most useful position.
        $newSysTemplateRows = [];
        $pidsBeforeSite = [0];
        $reversedRootline = array_reverse($rootline);
        foreach ($reversedRootline as $page) {
            if (($page['uid'] ?? 0) !== $siteRootPageId) {
                $pidsBeforeSite[] = (int)($page['uid'] ?? 0);
            } else {
                break;
            }
        }
        $pidsBeforeSite = array_unique($pidsBeforeSite);

        $fakeRowAdded = false;
        foreach ($sysTemplateRows as $sysTemplateRow) {
            if ($fakeRowAdded) {
                // We added the fake row already. Just add all other templates below this.
                $newSysTemplateRows[] = $sysTemplateRow;
                continue;
            }
            if (in_array((int)($sysTemplateRow['pid'] ?? 0), $pidsBeforeSite)) {
                $newSysTemplateRows[] = $sysTemplateRow;
                // If there is a sys_template row *before* our site, we assume settings from above
                // templates should "fall through", so we unset the clear flags. If this is not
                // wanted, an instance may need to register another event listener after this one
                // to set the clear flag again.
                $fakeRow['clear'] = 0;
            } elseif ((int)($sysTemplateRow['pid'] ?? 0) === $siteRootPageId) {
                // There is a sys_template on the site root page already. We add our fake row before
                // this one, then force the root and the clear flag of the sys_template row to 0.
                $newSysTemplateRows[] = $fakeRow;
                $fakeRowAdded = true;
                $sysTemplateRow['root'] = 0;
                $sysTemplateRow['clear'] = 0;
                $newSysTemplateRows[] = $sysTemplateRow;
            } else {
                // Not a sys_template row before, not an sys_template record on same page. Add our
                // fake row and mark we added it.
                $newSysTemplateRows[] = $fakeRow;
                $newSysTemplateRows[] = $sysTemplateRow;
                $fakeRowAdded = true;
            }
        }
        $event->setTemplateRows($newSysTemplateRows);
    }
}
