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

namespace TYPO3\CMS\Adminpanel\Utility;

use TYPO3\CMS\Core\Security\ContentSecurityPolicy\ConsumableNonce;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Html\HtmlWorker;

/**
 * Overrides Symfony's `HtmlDumper` to adjust inline style and JavaScript aspects.
 *
 * @internal
 */
final class HtmlDumper extends \Symfony\Component\VarDumper\Dumper\HtmlDumper
{
    protected ?ConsumableNonce $nonce = null;

    public function setNonce(ConsumableNonce $nonce): void
    {
        $this->nonce = $nonce;
        $this->dumpSuffix = str_replace(
            '<script>',
            sprintf('<script nonce="%s">', htmlspecialchars($nonce->consume())),
            $this->dumpSuffix
        );
    }

    /**
     * Transforms inline style and JavaScript elements to have a nonce attribute.
     */
    protected function getDumpHeader(): string
    {
        if ($this->dumpHeader !== null) {
            return $this->dumpHeader;
        }
        $dumpHeader = parent::getDumpHeader();
        if ($this->nonce === null) {
            return $dumpHeader;
        }
        $worker = GeneralUtility::makeInstance(HtmlWorker::class)
            ->parse($dumpHeader)
            ->addNonceAttribute($this->nonce, 'script', 'style');
        $this->dumpHeader = (string)$worker;
        return $this->dumpHeader;
    }
}
