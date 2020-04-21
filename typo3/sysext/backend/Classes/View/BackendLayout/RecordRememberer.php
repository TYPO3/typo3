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

namespace TYPO3\CMS\Backend\View\BackendLayout;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * @internal this is experimental and subject to change in TYPO3 v10 / v11
 */
class RecordRememberer implements SingletonInterface
{
    /**
     * @var int[]
     */
    protected $rememberedUids = [];

    public function rememberRecords(iterable $records): void
    {
        foreach ($records as $record) {
            $this->rememberRecordUid((int)($record['uid'] ?? 0));
            $this->rememberRecordUid((int)($record['l18n_parent'] ?? 0));
        }
    }

    public function rememberRecordUid(int $uid): void
    {
        $this->rememberedUids[$uid] = $uid;
    }

    public function isRemembered(int $uid): bool
    {
        return isset($this->rememberedUids[$uid]);
    }
}
