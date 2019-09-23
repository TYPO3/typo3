<?php
namespace TYPO3\CMS\Linkvalidator\Task;

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

use Symfony\Component\Mime\Address;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MailUtility;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * This class provides Scheduler plugin implementation
 * @internal This class is a specific Scheduler task implementation and is not part of the TYPO3's Core API.
 */
class ValidatorTask extends AbstractTask
{
    /**
     * @var int
     */
    protected $sleepTime;

    /**
     * @var int
     */
    protected $sleepAfterFinish;

    /**
     * @var int
     */
    protected $countInARun;

    /**
     * Total number of broken links
     *
     * @var int
     */
    protected $totalBrokenLink = 0;

    /**
     * Total number of broken links from the last run
     *
     * @var int
     */
    protected $oldTotalBrokenLink = 0;

    /**
     * Mail template fetched from the given template file
     *
     * @var string
     */
    protected $templateMail;

    /**
     * specific TSconfig for this task.
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * Shows if number of result was different from the result of the last check
     *
     * @var bool
     */
    protected $isDifferentToLastRun;

    /**
     * Template to be used for the email
     *
     * @var string
     */
    protected $emailTemplateFile;

    /**
     * Level of pages the task should check
     *
     * @var int
     */
    protected $depth;

    /**
     * UID of the start page for this task
     *
     * @var int
     */
    protected $page;

    /**
     * Email address to which an email report is sent
     *
     * @var string
     */
    protected $email;

    /**
     * Only send an email, if new broken links were found
     *
     * @var bool
     */
    protected $emailOnBrokenLinkOnly;

    /**
     * @var MarkerBasedTemplateService
     */
    protected $templateService;

    /**
     * Default language file of the extension linkvalidator
     *
     * @var string
     */
    protected $languageFile = 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf';

    /** @var BrokenLinkRepository */
    protected $brokenLinkRepository;

    /**
     * Get the value of the protected property email
     *
     * @return string Email address to which an email report is sent
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set the value of the private property email.
     *
     * @param string $email Email address to which an email report is sent
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * Get the value of the protected property emailOnBrokenLinkOnly
     *
     * @return bool Whether to send an email, if new broken links were found
     */
    public function getEmailOnBrokenLinkOnly()
    {
        return $this->emailOnBrokenLinkOnly;
    }

    /**
     * Set the value of the private property emailOnBrokenLinkOnly
     *
     * @param bool $emailOnBrokenLinkOnly Only send an email, if new broken links were found
     */
    public function setEmailOnBrokenLinkOnly($emailOnBrokenLinkOnly)
    {
        $this->emailOnBrokenLinkOnly = $emailOnBrokenLinkOnly;
    }

    /**
     * Get the value of the protected property page
     *
     * @return int UID of the start page for this task
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Set the value of the private property page
     *
     * @param int $page UID of the start page for this task.
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

    /**
     * Get the value of the protected property depth
     *
     * @return int Level of pages the task should check
     */
    public function getDepth()
    {
        return $this->depth;
    }

    /**
     * Set the value of the private property depth
     *
     * @param int $depth Level of pages the task should check
     */
    public function setDepth($depth)
    {
        $this->depth = $depth;
    }

    /**
     * Get the value of the protected property emailTemplateFile
     *
     * @return string Template to be used for the email
     */
    public function getEmailTemplateFile()
    {
        return $this->emailTemplateFile;
    }

    /**
     * Set the value of the private property emailTemplateFile
     *
     * @param string $emailTemplateFile Template to be used for the email
     */
    public function setEmailTemplateFile($emailTemplateFile)
    {
        $this->emailTemplateFile = $emailTemplateFile;
    }

    /**
     * Get the value of the protected property configuration
     *
     * @return array specific TSconfig for this task
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Set the value of the private property configuration
     *
     * @param array $configuration specific TSconfig for this task
     */
    public function setConfiguration($configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * Function execute from the Scheduler
     *
     * @return bool TRUE on successful execution, FALSE on error
     * @throws \InvalidArgumentException if the email template file can not be read
     */
    public function execute()
    {
        $this->brokenLinkRepository = GeneralUtility::makeInstance(BrokenLinkRepository::class);
        $this->setCliArguments();
        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $successfullyExecuted = true;
        if (!file_exists($file = GeneralUtility::getFileAbsFileName($this->emailTemplateFile))
            && !empty($this->email)
        ) {
            if ($this->emailTemplateFile === 'EXT:linkvalidator/res/mailtemplate.html') {
                // Update the default email template file path
                $this->emailTemplateFile = 'EXT:linkvalidator/Resources/Private/Templates/mailtemplate.html';
                $this->save();
            } else {
                $lang = $this->getLanguageService();
                throw new \InvalidArgumentException(
                    $lang->sL($this->languageFile . ':tasks.error.invalidEmailTemplateFile'),
                    '1295476972'
                );
            }
        }
        $htmlFile = file_get_contents($file);
        $this->templateMail = $this->templateService->getSubpart($htmlFile, '###REPORT_TEMPLATE###');
        // The array to put the content into
        $pageSections = '';
        $this->isDifferentToLastRun = false;
        $pageList = GeneralUtility::trimExplode(',', $this->page, true);
        $modTs = $this->loadModTsConfig($this->page);
        if (is_array($pageList)) {
            // reset broken link counts as they were stored in the serialized object
            $this->oldTotalBrokenLink = 0;
            $this->totalBrokenLink = 0;
            foreach ($pageList as $page) {
                $pageSections .= $this->checkPageLinks($page);
            }
        }
        if ($this->totalBrokenLink != $this->oldTotalBrokenLink) {
            $this->isDifferentToLastRun = true;
        }
        if ($this->totalBrokenLink > 0
            && (!$this->emailOnBrokenLinkOnly || $this->isDifferentToLastRun)
            && !empty($this->email)
        ) {
            $successfullyExecuted = $this->reportEmail($pageSections, $modTs);
        }
        return $successfullyExecuted;
    }

    /**
     * Validate all links for a page based on the task configuration
     *
     * @param int $page Uid of the page to parse
     * @return string $pageSections Content of page section
     * @throws \InvalidArgumentException
     */
    protected function checkPageLinks($page)
    {
        $pageRow = null;
        $page = (int)$page;
        $pageSections = '';
        $pageIds = '';
        $oldLinkCounts = [];
        $modTs = $this->loadModTsConfig($page);
        $searchFields = $this->getSearchField($modTs);
        $linkTypes = $this->getLinkTypes($modTs);
        /** @var LinkAnalyzer $processor */
        $processor = GeneralUtility::makeInstance(LinkAnalyzer::class);
        if ($page === 0) {
            $rootLineHidden = false;
        } else {
            $pageRow = BackendUtility::getRecord('pages', $page, '*', '', false);
            if ($pageRow === null) {
                throw new \InvalidArgumentException(
                    sprintf($this->getLanguageService()->sL($this->languageFile . ':tasks.error.invalidPageUid'), $page),
                    1502800555
                );
            }
            $rootLineHidden = $processor->getRootLineIsHidden($pageRow);
        }
        if (!$rootLineHidden || $modTs['checkhidden'] == 1) {
            $pageIds = $processor->extGetTreeList($page, $this->depth, 0, '1=1', $modTs['checkhidden']);
            if (isset($pageRow) && $pageRow['hidden'] == 0 || $modTs['checkhidden'] == 1) {
                // \TYPO3\CMS\Linkvalidator\LinkAnalyzer->extGetTreeList() always adds trailing comma
                $pageIds .= $page;
            }
        }
        if (!empty($pageIds)) {
            $processor->init($searchFields, $pageIds, $modTs);
            if (!empty($this->email)) {
                $oldLinkCounts = $processor->getLinkCounts();
                $this->oldTotalBrokenLink += $oldLinkCounts['total'];
            }
            $processor->getLinkStatistics($linkTypes, $modTs['checkhidden']);
            if (!empty($this->email)) {
                $linkCounts = $processor->getLinkCounts();
                $this->totalBrokenLink += $linkCounts['total'];
                $pageSections = $this->buildMail($page, $pageIds, $linkCounts, $oldLinkCounts);
            }
        }
        return $pageSections;
    }

    /**
     * Get the linkvalidator modTSconfig for a page
     *
     * @param int $page Uid of the page
     * @return array $modTsConfig mod.linkvalidator TSconfig array
     * @throws \Exception
     */
    protected function loadModTsConfig($page)
    {
        $parseObj = GeneralUtility::makeInstance(TypoScriptParser::class);
        $parseObj->parse($this->configuration);
        if (!empty($parseObj->errors)) {
            $languageService = $this->getLanguageService();
            $parseErrorMessage = $languageService->sL($this->languageFile . ':tasks.error.invalidTSconfig')
                . '<br />';
            foreach ($parseObj->errors as $errorInfo) {
                $parseErrorMessage .= $errorInfo[0] . '<br />';
            }
            throw new \Exception($parseErrorMessage, '1295476989');
        }
        $modTs = BackendUtility::getPagesTSconfig($page)['mod.']['linkvalidator.'] ?? [];
        $tsConfig = $parseObj->setup;
        $overrideTs = $tsConfig['mod.']['linkvalidator.'];
        if (is_array($overrideTs)) {
            ArrayUtility::mergeRecursiveWithOverrule($modTs, $overrideTs);
        }
        return $modTs;
    }

    /**
     * Get the list of fields to parse in modTSconfig
     *
     * @param array $modTS mod.linkvalidator TSconfig array
     * @return array $searchFields List of fields
     */
    protected function getSearchField(array $modTS)
    {
        $searchFields = [];
        // Get the searchFields from TypoScript
        foreach ($modTS['searchFields.'] as $table => $fieldList) {
            $fields = GeneralUtility::trimExplode(',', $fieldList);
            foreach ($fields as $field) {
                $searchFields[$table][] = $field;
            }
        }
        return $searchFields;
    }

    /**
     * Get the list of linkTypes to parse in modTSconfig
     *
     * @param array $modTS mod.linkvalidator TSconfig array
     * @return array $linkTypes list of link types
     */
    protected function getLinkTypes(array $modTS)
    {
        $linkTypes = [];
        $typesTmp = GeneralUtility::trimExplode(',', $modTS['linktypes'], true);
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] ?? [] as $type => $value) {
            if (in_array($type, $typesTmp)) {
                $linkTypes[$type] = 1;
            }
        }
        return $linkTypes;
    }

    /**
     * Build and send warning email when new broken links were found
     *
     * @param string $pageSections Content of page section
     * @param array $modTsConfig TSconfig array
     * @return bool TRUE if mail was sent, FALSE if or not
     * @throws \Exception if required modTsConfig settings are missing
     */
    protected function reportEmail($pageSections, array $modTsConfig)
    {
        $lang = $this->getLanguageService();
        $content = $this->templateService->substituteSubpart($this->templateMail, '###PAGE_SECTION###', $pageSections);
        $markerArray = [];
        $validEmailList = [];
        $markerArray['totalBrokenLink'] = $this->totalBrokenLink;
        $markerArray['totalBrokenLink_old'] = $this->oldTotalBrokenLink;

        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['reportEmailMarkers'] ?? [] as $userFunc) {
            $params = [
                'pObj' => &$this,
                'markerArray' => $markerArray
            ];
            $ref = $this; // introduced for phpstan to not lose type information when passing $this into callUserFunction
            $newMarkers = GeneralUtility::callUserFunction($userFunc, $params, $ref);
            if (is_array($newMarkers)) {
                $markerArray = $newMarkers + $markerArray;
            }
            unset($params);
        }
        $content = $this->templateService->substituteMarkerArray($content, $markerArray, '###|###', true, true);
        $mail = GeneralUtility::makeInstance(MailMessage::class);
        if (empty($modTsConfig['mail.']['fromemail'])) {
            $modTsConfig['mail.']['fromemail'] = MailUtility::getSystemFromAddress();
        }
        if (empty($modTsConfig['mail.']['fromname'])) {
            $modTsConfig['mail.']['fromname'] = MailUtility::getSystemFromName();
        }
        if (GeneralUtility::validEmail($modTsConfig['mail.']['fromemail'])) {
            $mail->from(new Address($modTsConfig['mail.']['fromemail'], $modTsConfig['mail.']['fromname']));
        } else {
            throw new \Exception(
                $lang->sL($this->languageFile . ':tasks.error.invalidFromEmail'),
                '1295476760'
            );
        }
        if (GeneralUtility::validEmail($modTsConfig['mail.']['replytoemail'])) {
            $mail->replyTo(new Address($modTsConfig['mail.']['replytoemail'], $modTsConfig['mail.']['replytoname']));
        }
        if (!empty($modTsConfig['mail.']['subject'])) {
            $mail->subject($modTsConfig['mail.']['subject']);
        } else {
            throw new \Exception(
                $lang->sL($this->languageFile . ':tasks.error.noSubject'),
                '1295476808'
            );
        }
        if (!empty($this->email)) {
            // Check if old input field value is still there and save the value a
            if (strpos($this->email, ',') !== false) {
                $emailList = GeneralUtility::trimExplode(',', $this->email, true);
                $this->email = implode(LF, $emailList);
                $this->save();
            } else {
                $emailList = GeneralUtility::trimExplode(LF, $this->email, true);
            }

            foreach ($emailList as $emailAdd) {
                if (!GeneralUtility::validEmail($emailAdd)) {
                    throw new \Exception(
                        $lang->sL($this->languageFile . ':tasks.error.invalidToEmail'),
                        '1295476821'
                    );
                }
                $validEmailList[] = $emailAdd;
            }
        }
        if (is_array($validEmailList) && !empty($validEmailList)) {
            $mail
                ->to(...$validEmailList)
                ->html($content)
                ->send();
            return true;
        }
        return false;
    }

    /**
     * Build the mail content
     *
     * @param int $curPage Id of the current page
     * @param string $pageList List of pages id
     * @param array $markerArray Array of markers
     * @param array $oldBrokenLink Marker array with the number of link found
     * @return string Content of the mail
     */
    protected function buildMail($curPage, $pageList, array $markerArray, array $oldBrokenLink)
    {
        $pageSectionHtml = $this->templateService->getSubpart($this->templateMail, '###PAGE_SECTION###');
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['buildMailMarkers'] ?? [] as $userFunc) {
            $params = [
                'curPage' => $curPage,
                'pageList' => $pageList,
                'markerArray' => $markerArray,
                'oldBrokenLink' => $oldBrokenLink,
                'pObj' => &$this
            ];
            $newMarkers = GeneralUtility::callUserFunction($userFunc, $params, $this);
            if (is_array($newMarkers)) {
                $markerArray = $newMarkers + $markerArray;
            }
            unset($params);
        }
        foreach ($markerArray as $markerKey => $markerValue) {
            if (empty($oldBrokenLink[$markerKey])) {
                $oldBrokenLink[$markerKey] = 0;
            }
            if ($markerValue != $oldBrokenLink[$markerKey]) {
                $this->isDifferentToLastRun = true;
            }
            $markerArray[$markerKey . '_old'] = $oldBrokenLink[$markerKey];
        }
        $markerArray['title'] = BackendUtility::getRecordTitle(
            'pages',
            BackendUtility::getRecord('pages', $curPage)
        );
        $content = '';
        if ($markerArray['total'] > 0) {
            $content = $this->templateService->substituteMarkerArray(
                $pageSectionHtml,
                $markerArray,
                '###|###',
                true,
                true
            );
        }
        return $content;
    }

    /**
     * Returns the most important properties of the link validator task as a
     * comma separated string that will be displayed in the scheduler module.
     *
     * @return string
     */
    public function getAdditionalInformation()
    {
        $additionalInformation = [];

        $page = (int)$this->getPage();
        $pageLabel = $page;
        if ($page !== 0) {
            $pageData = BackendUtility::getRecord('pages', $page);
            if (!empty($pageData)) {
                $pageTitle = BackendUtility::getRecordTitle('pages', $pageData);
                $pageLabel = $pageTitle . ' (' . $page . ')';
            }
        }
        $lang = $this->getLanguageService();
        $depth = (int)$this->getDepth();
        $additionalInformation[] = $lang->sL($this->languageFile . ':tasks.validate.page') . ': ' . $pageLabel;
        $additionalInformation[] = $lang->sL($this->languageFile . ':tasks.validate.depth') . ': '
            . $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_' . ($depth === 999 ? 'infi' : $depth));
        $additionalInformation[] = $lang->sL($this->languageFile . ':tasks.validate.email') . ': '
            . $this->getEmail();

        return implode(', ', $additionalInformation);
    }

    /**
     * Simulate cli call with setting the required options to the $_SERVER['argv']
     */
    protected function setCliArguments()
    {
        $_SERVER['argv'] = [
            $_SERVER['argv'][0],
            'tx_link_scheduler_link',
            '0',
            '-ss',
            '--sleepTime',
            $this->sleepTime,
            '--sleepAfterFinish',
            $this->sleepAfterFinish,
            '--countInARun',
            $this->countInARun
        ];
    }
}
