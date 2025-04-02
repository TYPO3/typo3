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

namespace TYPO3\CMS\Adminpanel\Modules\TsDebug;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleSettingsProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;

/**
 * @internal
 */
#[Autoconfigure(public: true)]
class TypoScriptWaterfall extends AbstractSubModule implements RequestEnricherInterface, ModuleSettingsProviderInterface
{
    protected array $printConf = [
        'showParentKeys' => true,
        'contentLength' => 10000,
        // Determines max length of displayed content before it gets cropped.
        'contentLength_FILE' => 400,
        // Determines max length of displayed content FROM FILE cObjects before it gets cropped. Reason is that most FILE cObjects are huge and often used as template-code.
        'flag_tree' => true,
        'flag_messages' => true,
        'flag_content' => false,
        'allTime' => false,
        'keyLgd' => 40,
    ];

    /**
     * Log entries that take than this number of milliseconds (own time) will be highlighted during
     * log display. Set 0 to disable highlighting.
     */
    protected int $highlightLongerThan = 0;

    public function __construct(
        private readonly ConfigurationService $configurationService,
        private readonly TimeTracker $timeTracker,
        private readonly ViewFactoryInterface $viewFactory,
    ) {}

    public function getIdentifier(): string
    {
        return 'typoscript-waterfall';
    }

    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_tsdebug.xlf:sub.waterfall.label'
        );
    }

    public function enrich(ServerRequestInterface $request): ServerRequestInterface
    {
        if ($this->getConfigurationOption('forceTemplateParsing')) {
            $cacheInstruction = $request->getAttribute('frontend.cache.instruction', new CacheInstruction());
            $cacheInstruction->disableCache('EXT:adminpanel: "Force TS rendering" disables cache.');
            $request = $request->withAttribute('frontend.cache.instruction', $cacheInstruction);
        }
        $this->timeTracker->LR = $this->getConfigurationOption('LR');
        return $request;
    }

    /**
     * Creates the content for the "tsdebug" section ("module") of the Admin Panel
     */
    public function getContent(ModuleData $data): string
    {
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:adminpanel/Resources/Private/Templates'],
            partialRootPaths: ['EXT:adminpanel/Resources/Private/Partials'],
            layoutRootPaths: ['EXT:adminpanel/Resources/Private/Layouts'],
        );
        $view = $this->viewFactory->create($viewFactoryData);
        $view->assignMultiple(
            [
                'tree' => (int)$this->getConfigurationOption('tree'),
                'display' => [
                    'times' => (int)$this->getConfigurationOption('displayTimes'),
                    'messages' => (int)$this->getConfigurationOption('displayMessages'),
                    'content' => (int)$this->getConfigurationOption('displayContent'),
                ],
                'trackContentRendering' => (int)$this->getConfigurationOption('LR'),
                'forceTemplateParsing' => (int)$this->getConfigurationOption('forceTemplateParsing'),
                'typoScriptLog' => $this->renderTypoScriptLog(),
                'languageKey' => $this->getBackendUser()->user['lang'] ?? null,
            ]
        );
        return $view->render('Modules/TsDebug/TypoScript');
    }

    public function getSettings(): string
    {
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:adminpanel/Resources/Private/Templates'],
            partialRootPaths: ['EXT:adminpanel/Resources/Private/Partials'],
            layoutRootPaths: ['EXT:adminpanel/Resources/Private/Layouts'],
        );
        $view = $this->viewFactory->create($viewFactoryData);
        $view->assignMultiple(
            [
                'tree' => (int)$this->getConfigurationOption('tree'),
                'display' => [
                    'times' => (int)$this->getConfigurationOption('displayTimes'),
                    'messages' => (int)$this->getConfigurationOption('displayMessages'),
                    'content' => (int)$this->getConfigurationOption('displayContent'),
                ],
                'trackContentRendering' => (int)$this->getConfigurationOption('LR'),
                'forceTemplateParsing' => (int)$this->getConfigurationOption('forceTemplateParsing'),
                'languageKey' => $this->getBackendUser()->user['lang'] ?? null,
            ]
        );
        return $view->render('Modules/TsDebug/TypoScriptSettings');
    }

    protected function getConfigurationOption(string $option): bool
    {
        return (bool)$this->configurationService->getConfigurationOption('tsdebug', $option);
    }

    /**
     * Renders the TypoScript log as string
     */
    protected function renderTypoScriptLog(): string
    {
        $this->printConf['flag_tree'] = $this->getConfigurationOption('tree');
        $this->printConf['allTime'] = $this->getConfigurationOption(
            'displayTimes'
        );
        $this->printConf['flag_messages'] = $this->getConfigurationOption(
            'displayMessages'
        );
        $this->printConf['flag_content'] = $this->getConfigurationOption(
            'displayContent'
        );
        return $this->printTSlog();
    }

    /**
     * Print TypoScript parsing log
     *
     * @return string HTML table with the information about parsing times.
     */
    protected function printTSlog(): string
    {
        $timeTracker = $this->timeTracker;
        if (!$timeTracker->isEnabled()) {
            return '';
        }
        $tsStackLog = $timeTracker->getTypoScriptLogStack();
        // Calculate times and keys for the tsStackLog
        foreach ($tsStackLog as &$data) {
            $data['endtime'] = $timeTracker->getDifferenceToStarttime($data['endtime'] ?? 0);
            $data['starttime'] = $timeTracker->getDifferenceToStarttime($data['starttime'] ?? 0);
            $data['deltatime'] = $data['endtime'] - $data['starttime'];
            if (isset($data['tsStack']) && is_array($data['tsStack'])) {
                $data['key'] = implode($data['stackPointer'] ? '.' : '/', end($data['tsStack']));
            }
        }
        unset($data);
        // Create hierarchical array of keys pointing to the stack
        $arr = [];
        foreach ($tsStackLog as $uniqueId => $data) {
            $this->createHierarchyArray($arr, $data['level'] ?? 0, (string)$uniqueId);
        }
        // Parsing the registered content and create icon-html for the tree
        $tsStackLog[$arr['0.'][0]]['content'] = $this->fixContent($tsStackLog, $arr['0.'], $tsStackLog[$arr['0.'][0]]['content'] ?? '', '', $arr['0.'][0]);
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
        foreach ($tsStackLog as $data) {
            $logRowClass = '';
            if ($this->highlightLongerThan && (int)$data['owntime'] > $this->highlightLongerThan) {
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
            $stackPointer = $data['stackPointer'] ?? false;
            if (!$flag_tree && $stackPointer) {
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
            $theLabel = $stackPointer ? '<span class="stackPointer">' . $theLabel . '</span>' : $theLabel;
            $keyLabel = $theLabel . $keyLabel;
            $item .= '<th scope="row" class="typo3-adminPanel-table-cell-key ' . $logRowClass . '">' . ($flag_tree ? $data['icons'] : '') . $this->fw($keyLabel) . '</th>';
            // Key value:
            $keyValue = $data['value'];
            $item .= '<td class="' . $logRowClass . ' typo3-adminPanel-tsLogTime">' . $this->fw(htmlspecialchars($keyValue)) . '</td>';
            $ownTime = (string)($data['owntime'] ?? '');
            if ($this->printConf['allTime']) {
                $item .= '<td class="' . $logRowClass . ' typo3-adminPanel-tsLogTime"> ' . $this->fw((string)$data['starttime']) . '</td>';
                $item .= '<td class="' . $logRowClass . ' typo3-adminPanel-tsLogTime"> ' . $this->fw($ownTime) . '</td>';
                $item .= '<td class="' . $logRowClass . ' typo3-adminPanel-tsLogTime"> ' . $this->fw(($data['subtime'] ? '+' . $data['subtime'] : '')) . '</td>';
                $item .= '<td class="' . $logRowClass . ' typo3-adminPanel-tsLogTime"> ' . $this->fw(($data['subtime'] ? '=' . $data['deltatime'] : '')) . '</td>';
            } else {
                $item .= '<td class="' . $logRowClass . ' typo3-adminPanel-tsLogTime"> ' . $this->fw($ownTime) . '</td>';
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
                // Break lines which are longer than $maxlen chars (can happen if content contains long paths...)
                if (preg_match_all('/(\\S{' . $maxlen . ',})/', $data['content'], $reg)) {
                    foreach ($reg[1] as $key => $match) {
                        $match = preg_replace('/(.{' . $maxlen . '})/', '$1 ', $match);
                        $data['content'] = str_replace($reg[0][$key], $match, $data['content']);
                    }
                }
                $msgArr[] = nl2br($data['content']);
            }
            if (!empty($msgArr)) {
                $msg = implode('<br>', $msgArr);
            }
            $item .= '<td class="typo3-adminPanel-table-cell-content">' . $this->fw($msg) . '</td>';
            $out .= '<tr>' . $item . '</tr>';
            $c++;
        }
        return '<div class="typo3-adminPanel-table-overflow"><table class="typo3-adminPanel-table typo3-adminPanel-table-debug">' . $out . '</table></div>';
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
    protected function fixContent(array &$tsStackLog, array &$arr, string $content, string $depthData = '', string $vKey = ''): string
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

                $tsStackLog[$v]['icons'] = $depthData . $PM;
                if (($tsStackLog[$v]['content'] ?? '') !== '') {
                    $content = str_replace($tsStackLog[$v]['content'], $v, $content);
                }
                if ($hasChildren) {
                    $lineClass = $lastEntry ? 'treeline-icon-clear' : 'treeline-icon-line';
                    $tsStackLog[$v]['content'] = $this->fixContent(
                        $tsStackLog,
                        $arr[$k . '.'],
                        ($tsStackLog[$v]['content'] ?? ''),
                        $depthData . '<span class="treeline-icon ' . $lineClass . '"></span>',
                        $v
                    );
                } else {
                    $tsStackLog[$v]['content'] = $this->fixCLen(($tsStackLog[$v]['content'] ?? ''), $tsStackLog[$v]['value']);
                    $tsStackLog[$v]['subtime'] = '';
                    $tsStackLog[$v]['owntime'] = $tsStackLog[$v]['deltatime'];
                }
                $subtime += $tsStackLog[$v]['deltatime'];
            }
        }
        // Set content with special chars
        if (isset($tsStackLog[$vKey])) {
            $tsStackLog[$vKey]['subtime'] = $subtime;
            $tsStackLog[$vKey]['owntime'] = $tsStackLog[$vKey]['deltatime'] - $subtime;
        }
        $content = $this->fixCLen($content, $tsStackLog[$vKey]['value']);
        // Traverse array again, this time substitute the unique hash with the red key
        foreach ($arr as $k => $v) {
            if (MathUtility::canBeInterpretedAsInteger($k)) {
                if ($tsStackLog[$v]['content'] !== '') {
                    $content = str_replace($v, '<strong style="color:red;">[' . $tsStackLog[$v]['key'] . ']</strong>', $content);
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
     */
    protected function fixCLen(string $c, string $v): string
    {
        $len = (int)($v === 'FILE' ? $this->printConf['contentLength_FILE'] : $this->printConf['contentLength']);
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
     */
    protected function fw(string $str): string
    {
        return '<span>' . $str . '</span>';
    }

    /**
     * Helper function for internal data manipulation
     *
     * @param array $arr Array (passed by reference) and modified
     * @param int $pointer Pointer value
     * @param string $uniqueId Unique ID string
     * @see printTSlog()
     */
    protected function createHierarchyArray(array &$arr, int $pointer, string $uniqueId): void
    {
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
