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

namespace TYPO3\CMS\Core\TimeTracker;

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Frontend Timetracking functions
 * Is used to register how much time is used with operations in TypoScript.
 *
 * Note: Only push() (with first argument only), pull() and setTSlogMessage()
 *       are considered API, everything else is internal.
 */
class TimeTracker implements SingletonInterface
{
    /**
     * If set to true (see constructor) then timetracking is enabled
     */
    protected bool $isEnabled = false;

    /**
     * Is loaded with the millisecond time when this object is created
     */
    protected int $starttime = 0;

    /**
     * Is set via finish() with the millisecond time when the request handler is finished.
     */
    protected float $finishtime = 0;

    /**
     * Log Rendering flag. If set, ->push() and ->pull() is called from the cObj->cObjGetSingle().
     * This determines whether the TypoScript parsing activity is logged. But it also slows down the rendering.
     *
     * @internal
     */
    public bool $LR = true;

    protected array $wrapError = [
        LogLevel::INFO => ['', ''],
        LogLevel::NOTICE => ['<strong>', '</strong>'],
        LogLevel::WARNING => ['<strong style="color:#ff6600;">', '</strong>'],
        LogLevel::ERROR => ['<strong style="color:#ff0000;">', '</strong>'],
    ];

    protected array $wrapIcon = [
        LogLevel::INFO => '',
        LogLevel::NOTICE => 'actions-document-info',
        LogLevel::WARNING => 'status-dialog-warning',
        LogLevel::ERROR => 'status-dialog-error',
    ];

    protected int $uniqueCounter = 0;
    protected array $tsStack = [[]];
    protected int $tsStackLevel = 0;
    protected array $tsStackLevelMax = [];
    protected array $tsStackLog = [];
    protected int $tsStackPointer = 0;
    protected array $currentHashPointer = [];

    /**
     * @internal
     */
    public function __construct(bool $isEnabled = true)
    {
        $this->isEnabled = $isEnabled;
    }

    /**
     * Pushes an element to the TypoScript tracking array
     *
     * @param string $tslabel Label string for the entry, eg. TypoScript property name
     * @param string $value Additional value (@internal, may vanish)
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::cObjGetSingle()
     * @see pull()
     */
    public function push(string $tslabel, string $value = ''): void
    {
        if (!$this->isEnabled) {
            return;
        }
        $this->tsStack[$this->tsStackPointer][] = $tslabel;
        $this->currentHashPointer[] = 'timetracker_' . $this->uniqueCounter++;
        $this->tsStackLevel++;
        $this->tsStackLevelMax[] = $this->tsStackLevel;
        // setTSlog
        $k = end($this->currentHashPointer);
        $this->tsStackLog[$k] = [
            'level' => $this->tsStackLevel,
            'tsStack' => $this->tsStack,
            'value' => $value,
            'starttime' => microtime(true),
            'stackPointer' => $this->tsStackPointer,
        ];
    }

    /**
     * Pulls an element from the TypoScript tracking array
     *
     * @param string $content The content string generated within the push/pull part.
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::cObjGetSingle()
     * @see push()
     */
    public function pull(string $content = ''): void
    {
        if (!$this->isEnabled) {
            return;
        }
        $k = end($this->currentHashPointer);
        $this->tsStackLog[$k]['endtime'] = microtime(true);
        $this->tsStackLog[$k]['content'] = $content;
        $this->tsStackLevel--;
        array_pop($this->tsStack[$this->tsStackPointer]);
        array_pop($this->currentHashPointer);
    }

    /**
     * Logs the TypoScript entry
     *
     * @param string $content The message string
     * @param string $logLevel Message type: see LogLevel constants
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::CONTENT()
     */
    public function setTSlogMessage(string $content, string $logLevel = LogLevel::INFO): void
    {
        if (!$this->isEnabled) {
            return;
        }
        end($this->currentHashPointer);
        $k = current($this->currentHashPointer);
        $placeholder = '';
        // Enlarge the "details" column by adding a span
        if (strlen($content) > 30) {
            $placeholder = '<br /><span style="width: 300px; height: 1px; display: inline-block;"></span>';
        }
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->tsStackLog[$k]['message'][] = $iconFactory->getIcon($this->wrapIcon[$logLevel], IconSize::SMALL)->render() . $this->wrapError[$logLevel][0] . htmlspecialchars($content) . $this->wrapError[$logLevel][1] . $placeholder;
    }

    /**
     * @internal
     */
    public function setEnabled(bool $isEnabled = true): void
    {
        $this->isEnabled = $isEnabled;
    }

    /**
     * Sets the starting time
     *
     * @see finish()
     * @internal
     */
    public function start(?float $starttime = null): void
    {
        if (!$this->isEnabled) {
            return;
        }
        $this->starttime = $this->getMilliseconds($starttime);
    }

    /**
     * Increases the stack pointer
     *
     * @see decStackPointer()
     * @see \TYPO3\CMS\Frontend\Page\PageGenerator::renderContent()
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::cObjGetSingle()
     * @internal
     */
    public function incStackPointer(): void
    {
        if (!$this->isEnabled) {
            return;
        }
        $this->tsStackPointer++;
        $this->tsStack[$this->tsStackPointer] = [];
    }

    /**
     * Decreases the stack pointer
     *
     * @see incStackPointer()
     * @see \TYPO3\CMS\Frontend\Page\PageGenerator::renderContent()
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::cObjGetSingle()
     * @internal
     */
    public function decStackPointer(): void
    {
        if (!$this->isEnabled) {
            return;
        }
        unset($this->tsStack[$this->tsStackPointer]);
        $this->tsStackPointer--;
    }

    /**
     * Gets a microtime value as milliseconds value.
     *
     * @param float|null $microtime The microtime value - if not set the current time is used
     * @return int The microtime value as milliseconds value
     */
    protected function getMilliseconds(?float $microtime = null): int
    {
        if (!$this->isEnabled) {
            return 0;
        }
        if ($microtime === null) {
            $microtime = microtime(true);
        }
        return (int)round($microtime * 1000);
    }

    /**
     * Gets the difference between a given microtime value and the starting time as milliseconds.
     *
     * @param float|null $microtime The microtime value - if not set the current time is used
     * @return int The difference between a given microtime value and starting time as milliseconds
     * @internal
     */
    public function getDifferenceToStarttime(?float $microtime = null): int
    {
        return $this->getMilliseconds($microtime) - $this->starttime;
    }

    /**
     * Usually called when the page generation and output is prepared.
     *
     * @see start()
     * @internal
     */
    public function finish(): void
    {
        if ($this->isEnabled) {
            $this->finishtime = microtime(true);
        }
    }

    /**
     * Get total parse time in milliseconds
     * @internal
     */
    public function getParseTime(): int
    {
        if (!$this->starttime) {
            $this->start(microtime(true));
        }
        if (!$this->finishtime) {
            $this->finish();
        }
        return $this->getDifferenceToStarttime($this->finishtime ?? null);
    }

    /**
     * @internal
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * @internal
     */
    public function getTypoScriptLogStack(): array
    {
        return $this->tsStackLog;
    }
}
