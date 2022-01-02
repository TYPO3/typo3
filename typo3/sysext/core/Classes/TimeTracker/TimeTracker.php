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

namespace TYPO3\CMS\Core\TimeTracker;

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Frontend Timetracking functions
 *
 * Is used to register how much time is used with operations in TypoScript
 */
class TimeTracker implements SingletonInterface
{
    /**
     * If set to true (see constructor) then then the timetracking is enabled
     * @var bool
     */
    protected $isEnabled = false;

    /**
     * Is loaded with the millisecond time when this object is created
     *
     * @var int
     */
    public $starttime = 0;

    /**
     * Is set via finish() with the millisecond time when the request handler is finished.
     *
     * @var float
     */
    protected $finishtime = 0;

    /**
     * Log Rendering flag. If set, ->push() and ->pull() is called from the cObj->cObjGetSingle().
     * This determines whether or not the TypoScript parsing activity is logged. But it also slows down the rendering
     *
     * @var bool
     */
    public $LR = true;

    /**
     * @var array
     */
    public $printConf = [
        'showParentKeys' => 1,
        'contentLength' => 10000,
        // Determines max length of displayed content before it gets cropped.
        'contentLength_FILE' => 400,
        // Determines max length of displayed content FROM FILE cObjects before it gets cropped. Reason is that most FILE cObjects are huge and often used as template-code.
        'flag_tree' => 1,
        'flag_messages' => 1,
        'flag_content' => 0,
        'allTime' => 0,
        'keyLgd' => 40,
    ];

    /**
     * @var array
     */
    public $wrapError = [
        // the numeric items can be removed in TYPO3 v12.0.
        0 => ['', ''],
        1 => ['<strong>', '</strong>'],
        2 => ['<strong style="color:#ff6600;">', '</strong>'],
        3 => ['<strong style="color:#ff0000;">', '</strong>'],
        LogLevel::INFO => ['', ''],
        LogLevel::NOTICE => ['<strong>', '</strong>'],
        LogLevel::WARNING => ['<strong style="color:#ff6600;">', '</strong>'],
        LogLevel::ERROR => ['<strong style="color:#ff0000;">', '</strong>'],
    ];

    /**
     * @var array
     */
    public $wrapIcon = [
        // the numeric items can be removed in TYPO3 v12.0.
        0 => '',
        1 => 'actions-document-info',
        2 => 'status-dialog-warning',
        3 => 'status-dialog-error',
        LogLevel::INFO => '',
        LogLevel::NOTICE => 'actions-document-info',
        LogLevel::WARNING => 'status-dialog-warning',
        LogLevel::ERROR => 'status-dialog-error',
    ];

    /**
     * @var int
     */
    public $uniqueCounter = 0;

    /**
     * @var array
     */
    public $tsStack = [[]];

    /**
     * @var int
     */
    public $tsStackLevel = 0;

    /**
     * @var array
     */
    public $tsStackLevelMax = [];

    /**
     * @var array
     */
    public $tsStackLog = [];

    /**
     * @var int
     */
    public $tsStackPointer = 0;

    /**
     * @var array
     */
    public $currentHashPointer = [];

    /**
     * Log entries that take than this number of milliseconds (own time) will be highlighted during log display. Set 0 to disable highlighting.
     *
     * @var int
     */
    public $highlightLongerThan = 0;

    /*******************************************
     *
     * Logging parsing times in the scripts
     *
     *******************************************/

    /**
     * TimeTracker constructor.
     *
     * @param bool $isEnabled
     */
    public function __construct($isEnabled = true)
    {
        $this->isEnabled = $isEnabled;
    }

    /**
     * @param bool $isEnabled
     */
    public function setEnabled(bool $isEnabled = true)
    {
        $this->isEnabled = $isEnabled;
    }

    /**
     * Sets the starting time
     *
     * @see finish()
     * @param float|null $starttime
     */
    public function start(?float $starttime = null)
    {
        if (!$this->isEnabled) {
            return;
        }
        $this->starttime = $this->getMilliseconds($starttime);
    }

    /**
     * Pushes an element to the TypoScript tracking array
     *
     * @param string $tslabel Label string for the entry, eg. TypoScript property name
     * @param string $value Additional value(?)
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::cObjGetSingle()
     * @see pull()
     */
    public function push($tslabel, $value = '')
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
    public function pull($content = '')
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
     * @param string|int $logLevel Message type: 0: information, 1: message, 2: warning, 3: error or the LogLevel constants - will become the LogLevel constants only in TYPO3 v12.0.
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::CONTENT()
     */
    public function setTSlogMessage($content, $logLevel = LogLevel::INFO)
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
        $this->tsStackLog[$k]['message'][] = $iconFactory->getIcon($this->wrapIcon[$logLevel], Icon::SIZE_SMALL)->render() . $this->wrapError[$logLevel][0] . htmlspecialchars($content) . $this->wrapError[$logLevel][1] . $placeholder;
    }

    /**
     * Set TSselectQuery - for messages in TypoScript debugger.
     *
     * @param array $data Query array
     * @param string $msg Message/Label to attach
     */
    public function setTSselectQuery(array $data, $msg = '')
    {
        if (!$this->isEnabled) {
            return;
        }
        end($this->currentHashPointer);
        $k = current($this->currentHashPointer);
        if ($msg !== '') {
            $data['msg'] = $msg;
        }
        $this->tsStackLog[$k]['selectQuery'][] = $data;
    }

    /**
     * Increases the stack pointer
     *
     * @see decStackPointer()
     * @see \TYPO3\CMS\Frontend\Page\PageGenerator::renderContent()
     * @see \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::cObjGetSingle()
     */
    public function incStackPointer()
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
     */
    public function decStackPointer()
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
     * @param float $microtime The microtime value - if not set the current time is used
     * @return int The microtime value as milliseconds value
     */
    public function getMilliseconds($microtime = null)
    {
        if (!$this->isEnabled) {
            return 0;
        }
        if (!isset($microtime)) {
            $microtime = microtime(true);
        }
        return (int)round($microtime * 1000);
    }

    /**
     * Gets the difference between a given microtime value and the starting time as milliseconds.
     *
     * @param float $microtime The microtime value - if not set the current time is used
     * @return int The difference between a given microtime value and starting time as milliseconds
     */
    public function getDifferenceToStarttime($microtime = null)
    {
        return $this->getMilliseconds($microtime) - $this->starttime;
    }

    /**
     * Usually called when the page generation and output is prepared.
     *
     * @see start()
     */
    public function finish(): void
    {
        if ($this->isEnabled) {
            $this->finishtime = microtime(true);
        }
    }

    /**
     * Get total parse time in milliseconds
     *
     * @return int
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

    /*******************************************
     *
     * Printing the parsing time information (for Admin Panel)
     *
     *******************************************/
    /**
     * Print TypoScript parsing log
     *
     * @return string HTML table with the information about parsing times.
     */
    public function printTSlog()
    {
        if (!$this->isEnabled) {
            return '';
        }
        // Calculate times and keys for the tsStackLog
        foreach ($this->tsStackLog as $uniqueId => &$data) {
            $data['endtime'] = $this->getDifferenceToStarttime($data['endtime'] ?? 0);
            $data['starttime'] = $this->getDifferenceToStarttime($data['starttime'] ?? 0);
            $data['deltatime'] = $data['endtime'] - $data['starttime'];
            if (isset($data['tsStack']) && is_array($data['tsStack'])) {
                $data['key'] = implode($data['stackPointer'] ? '.' : '/', end($data['tsStack']));
            }
        }
        unset($data);
        // Create hierarchical array of keys pointing to the stack
        $arr = [];
        foreach ($this->tsStackLog as $uniqueId => $data) {
            $this->createHierarchyArray($arr, $data['level'] ?? 0, $uniqueId);
        }
        // Parsing the registered content and create icon-html for the tree
        $this->tsStackLog[$arr['0.'][0]]['content'] = $this->fixContent($arr['0.'], $this->tsStackLog[$arr['0.'][0]]['content'] ?? '', '', $arr['0.'][0]);
        // Displaying the tree:
        $outputArr = [];
        $outputArr[] = $this->fw('TypoScript Key');
        $outputArr[] = $this->fw('Value');
        if ($this->printConf['allTime']) {
            $outputArr[] = $this->fw('Time');
            $outputArr[] = $this->fw('Own');
            $outputArr[] = $this->fw('Sub');
            $outputArr[] = $this->fw('Total');
        } else {
            $outputArr[] = $this->fw('Own');
        }
        $outputArr[] = $this->fw('Details');
        $out = '';
        foreach ($outputArr as $row) {
            $out .= '<th>' . $row . '</th>';
        }
        $out = '<thead><tr>' . $out . '</tr></thead>';
        $flag_tree = $this->printConf['flag_tree'];
        $flag_messages = $this->printConf['flag_messages'];
        $flag_content = $this->printConf['flag_content'];
        $keyLgd = (int)$this->printConf['keyLgd'];
        $c = 0;
        foreach ($this->tsStackLog as $uniqueId => $data) {
            $logRowClass = '';
            if ($this->highlightLongerThan && (int)$data['owntime'] > (int)$this->highlightLongerThan) {
                $logRowClass = 'typo3-adminPanel-logRow-highlight';
            }
            $item = '';
            // If first...
            if (!$c) {
                $data['icons'] = '';
                $data['key'] = 'Script Start';
                $data['value'] = '';
            }
            // Key label:
            $keyLabel = '';
            if (!$flag_tree && $data['stackPointer']) {
                $temp = [];
                foreach ($data['tsStack'] as $k => $v) {
                    $temp[] = GeneralUtility::fixed_lgd_cs(implode($k ? '.' : '/', $v), -$keyLgd);
                }
                array_pop($temp);
                $temp = array_reverse($temp);
                array_pop($temp);
                if (!empty($temp)) {
                    $keyLabel = '<br /><span style="color:#999999;">' . implode('<br />', $temp) . '</span>';
                }
            }
            if ($flag_tree) {
                $tmp = GeneralUtility::trimExplode('.', $data['key'], true);
                $theLabel = end($tmp);
            } else {
                $theLabel = $data['key'];
            }
            $theLabel = GeneralUtility::fixed_lgd_cs($theLabel, -$keyLgd);
            $theLabel = $data['stackPointer'] ? '<span class="stackPointer">' . $theLabel . '</span>' : $theLabel;
            $keyLabel = $theLabel . $keyLabel;
            $item .= '<th scope="row" class="typo3-adminPanel-table-cell-key ' . $logRowClass . '">' . ($flag_tree ? $data['icons'] : '') . $this->fw($keyLabel) . '</th>';
            // Key value:
            $keyValue = $data['value'];
            $item .= '<td class="' . $logRowClass . ' typo3-adminPanel-tsLogTime">' . $this->fw(htmlspecialchars($keyValue)) . '</td>';
            if ($this->printConf['allTime']) {
                $item .= '<td class="' . $logRowClass . ' typo3-adminPanel-tsLogTime"> ' . $this->fw((string)$data['starttime']) . '</td>';
                $item .= '<td class="' . $logRowClass . ' typo3-adminPanel-tsLogTime"> ' . $this->fw((string)$data['owntime']) . '</td>';
                $item .= '<td class="' . $logRowClass . ' typo3-adminPanel-tsLogTime"> ' . $this->fw(($data['subtime'] ? '+' . $data['subtime'] : '')) . '</td>';
                $item .= '<td class="' . $logRowClass . ' typo3-adminPanel-tsLogTime"> ' . $this->fw(($data['subtime'] ? '=' . $data['deltatime'] : '')) . '</td>';
            } else {
                $item .= '<td class="' . $logRowClass . ' typo3-adminPanel-tsLogTime"> ' . $this->fw((string)$data['owntime']) . '</td>';
            }
            // Messages:
            $msgArr = [];
            $msg = '';
            if ($flag_messages && is_array($data['message'] ?? null)) {
                foreach ($data['message'] as $v) {
                    $msgArr[] = nl2br($v);
                }
            }
            if ($flag_content && (string)$data['content'] !== '') {
                $maxlen = 120;
                // Break lines which are too longer than $maxlen chars (can happen if content contains long paths...)
                if (preg_match_all('/(\\S{' . $maxlen . ',})/', $data['content'], $reg)) {
                    foreach ($reg[1] as $key => $match) {
                        $match = preg_replace('/(.{' . $maxlen . '})/', '$1 ', $match);
                        $data['content'] = str_replace($reg[0][$key], $match, $data['content']);
                    }
                }
                $msgArr[] = nl2br($data['content']);
            }
            if (!empty($msgArr)) {
                $msg = implode('<hr />', $msgArr);
            }
            $item .= '<td class="typo3-adminPanel-table-cell-content">' . $this->fw($msg) . '</td>';
            $out .= '<tr>' . $item . '</tr>';
            $c++;
        }
        $out = '<div class="typo3-adminPanel-table-overflow"><table class="typo3-adminPanel-table typo3-adminPanel-table-debug">' . $out . '</table></div>';
        return $out;
    }

    /**
     * Recursively generates the content to display
     *
     * @param array $arr Array which is modified with content. Reference
     * @param string $content Current content string for the level
     * @param string $depthData Prefixed icons for new PM icons
     * @param string $vKey Seems to be the previous tsStackLog key
     * @return string Returns the $content string generated/modified. Also the $arr array is modified!
     */
    protected function fixContent(&$arr, $content, $depthData = '', $vKey = '')
    {
        $entriesCount = 0;
        $c = 0;
        // First, find number of entries
        foreach ($arr as $k => $v) {
            //do not count subentries (the one ending with dot, eg. '9.'
            if (MathUtility::canBeInterpretedAsInteger($k)) {
                $entriesCount++;
            }
        }
        // Traverse through entries
        $subtime = 0;
        foreach ($arr as $k => $v) {
            if (MathUtility::canBeInterpretedAsInteger($k)) {
                $c++;
                $hasChildren = isset($arr[$k . '.']);
                $lastEntry = $entriesCount === $c;

                $PM = '<span class="treeline-icon treeline-icon-join' . ($lastEntry ? 'bottom' : '') . '"></span>';

                $this->tsStackLog[$v]['icons'] = $depthData . $PM;
                if (($this->tsStackLog[$v]['content'] ?? '') !== '') {
                    $content = str_replace($this->tsStackLog[$v]['content'], $v, $content);
                }
                if ($hasChildren) {
                    $lineClass = $lastEntry ? 'treeline-icon-clear' : 'treeline-icon-line';
                    $this->tsStackLog[$v]['content'] = $this->fixContent(
                        $arr[$k . '.'],
                        ($this->tsStackLog[$v]['content'] ?? ''),
                        $depthData . '<span class="treeline-icon ' . $lineClass . '"></span>',
                        $v
                    );
                } else {
                    $this->tsStackLog[$v]['content'] = $this->fixCLen(($this->tsStackLog[$v]['content'] ?? ''), $this->tsStackLog[$v]['value']);
                    $this->tsStackLog[$v]['subtime'] = '';
                    $this->tsStackLog[$v]['owntime'] = $this->tsStackLog[$v]['deltatime'];
                }
                $subtime += $this->tsStackLog[$v]['deltatime'];
            }
        }
        // Set content with special chars
        if (isset($this->tsStackLog[$vKey])) {
            $this->tsStackLog[$vKey]['subtime'] = $subtime;
            $this->tsStackLog[$vKey]['owntime'] = $this->tsStackLog[$vKey]['deltatime'] - $subtime;
        }
        $content = $this->fixCLen($content, $this->tsStackLog[$vKey]['value']);
        // Traverse array again, this time substitute the unique hash with the red key
        foreach ($arr as $k => $v) {
            if (MathUtility::canBeInterpretedAsInteger($k)) {
                if ($this->tsStackLog[$v]['content'] !== '') {
                    $content = str_replace($v, '<strong style="color:red;">[' . $this->tsStackLog[$v]['key'] . ']</strong>', $content);
                }
            }
        }
        // Return the content
        return $content;
    }

    /**
     * Wraps the input content string in green colored span-tags IF the length of the input string exceeds $this->printConf['contentLength'] (or $this->printConf['contentLength_FILE'] if $v == "FILE"
     *
     * @param string $c The content string
     * @param string $v Command: If "FILE" then $this->printConf['contentLength_FILE'] is used for content length comparison, otherwise $this->printConf['contentLength']
     * @return string
     */
    protected function fixCLen($c, $v)
    {
        $len = $v === 'FILE' ? $this->printConf['contentLength_FILE'] : $this->printConf['contentLength'];
        if (strlen($c) > $len) {
            $c = '<span style="color:green;">' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($c, $len)) . '</span>';
        } else {
            $c = htmlspecialchars($c);
        }
        return $c;
    }

    /**
     * Wraps input string in a <span> tag
     *
     * @param string $str The string to be wrapped
     * @return string
     */
    protected function fw($str)
    {
        return '<span>' . $str . '</span>';
    }

    /**
     * Helper function for internal data manipulation
     *
     * @param array $arr Array (passed by reference) and modified
     * @param int $pointer Pointer value
     * @param string $uniqueId Unique ID string
     * @internal
     * @see printTSlog()
     */
    protected function createHierarchyArray(&$arr, $pointer, $uniqueId)
    {
        if (!is_array($arr)) {
            $arr = [];
        }
        if ($pointer > 0) {
            end($arr);
            $k = key($arr);
            if (!is_array($arr[(int)$k . '.'] ?? null)) {
                $arr[(int)$k . '.'] = [];
            }
            $this->createHierarchyArray($arr[(int)$k . '.'], $pointer - 1, $uniqueId);
        } else {
            $arr[] = $uniqueId;
        }
    }
}
