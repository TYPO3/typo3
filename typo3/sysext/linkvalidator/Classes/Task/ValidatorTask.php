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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MailUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;

/**
 * This class provides Scheduler plugin implementation
 */
class ValidatorTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
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
     * @return void
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
     * @return void
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
     * @return void
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
     * @return void
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
     * @return void
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
     * @return void
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
        $this->setCliArguments();
        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
        $successfullyExecuted = true;
        if (
            !file_exists(($file = GeneralUtility::getFileAbsFileName($this->emailTemplateFile)))
            && !empty($this->email)
        ) {
            if ($this->emailTemplateFile === 'EXT:linkvalidator/res/mailtemplate.html') {
                // Update the default email template file path
                $this->emailTemplateFile = 'EXT:linkvalidator/Resources/Private/Templates/mailtemplate.html';
                $this->save();
            } else {
                throw new \InvalidArgumentException(
                    $this->getLanguageService()->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.error.invalidEmailTemplateFile'),
                    '1295476972'
                );
            }
        }
        $htmlFile = GeneralUtility::getURL($file);
        $this->templateMail = $this->templateService->getSubpart($htmlFile, '###REPORT_TEMPLATE###');
        // The array to put the content into
        $pageSections = '';
        $this->isDifferentToLastRun = false;
        $pageList = GeneralUtility::trimExplode(',', $this->page, true);
        $modTs = $this->loadModTsConfig($this->page);
        if (is_array($pageList)) {
            foreach ($pageList as $page) {
                $pageSections .= $this->checkPageLinks($page);
            }
        }
        if ($this->totalBrokenLink != $this->oldTotalBrokenLink) {
            $this->isDifferentToLastRun = true;
        }
        if ($this->totalBrokenLink > 0 && (!$this->emailOnBrokenLinkOnly || $this->isDifferentToLastRun) && !empty($this->email)) {
            $successfullyExecuted = $this->reportEmail($pageSections, $modTs);
        }
        return $successfullyExecuted;
    }

    /**
     * Validate all links for a page based on the task configuration
     *
     * @param int $page Uid of the page to parse
     * @return string $pageSections Content of page section
     */
    protected function checkPageLinks($page)
    {
        $page = (int)$page;
        $pageSections = '';
        $pageIds = '';
        $oldLinkCounts = [];
        $modTs = $this->loadModTsConfig($page);
        $searchFields = $this->getSearchField($modTs);
        $linkTypes = $this->getLinkTypes($modTs);
        /** @var $processor LinkAnalyzer */
        $processor = GeneralUtility::makeInstance(LinkAnalyzer::class);
        if ($page === 0) {
            $rootLineHidden = false;
        } else {
            $pageRow = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'pages', 'uid=' . $page);
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
                $oldLinkCounts = $processor->getLinkCounts($page);
                $this->oldTotalBrokenLink += $oldLinkCounts['brokenlinkCount'];
            }
            $processor->getLinkStatistics($linkTypes, $modTs['checkhidden']);
            if (!empty($this->email)) {
                $linkCounts = $processor->getLinkCounts($page);
                $this->totalBrokenLink += $linkCounts['brokenlinkCount'];
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
        $modTs = BackendUtility::getModTSconfig($page, 'mod.linkvalidator');
        $parseObj = GeneralUtility::makeInstance(TypoScriptParser::class);
        $parseObj->parse($this->configuration);
        if (!empty($parseObj->errors)) {
            $parseErrorMessage = $this->getLanguageService()->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.error.invalidTSconfig') . '<br />';
            foreach ($parseObj->errors as $errorInfo) {
                $parseErrorMessage .= $errorInfo[0] . '<br />';
            }
            throw new \Exception($parseErrorMessage, '1295476989');
        }
        $tsConfig = $parseObj->setup;
        $modTs = $modTs['properties'];
        $overrideTs = $tsConfig['mod.']['linkvalidator.'];
        if (is_array($overrideTs)) {
            ArrayUtility::mergeRecursiveWithOverrule($modTs, $overrideTs);
        } else {
            $deprecatedOverrideTs = $tsConfig['mod.']['tx_linkvalidator.'];
            if (is_array($deprecatedOverrideTs)) {
                GeneralUtility::deprecationLog('Using mod.tx_linkvalidator in the scheduler TSConfig setting is deprecated since TYPO3 CMS 7 and will be removed in TYPO3 CMS 8. Please use mod.linkvalidator instead.');
                ArrayUtility::mergeRecursiveWithOverrule($modTs, $deprecatedOverrideTs);
            }
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
        // Get the searchFields from TypoScript
        foreach ($modTS['searchFields.'] as $table => $fieldList) {
            $fields = GeneralUtility::trimExplode(',', $fieldList);
            foreach ($fields as $field) {
                $searchFields[$table][] = $field;
            }
        }
        return isset($searchFields) ? $searchFields : [];
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
        if (is_array($typesTmp)) {
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks']) && is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] as $type => $value) {
                    if (in_array($type, $typesTmp)) {
                        $linkTypes[$type] = 1;
                    }
                }
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
        $content = $this->templateService->substituteSubpart($this->templateMail, '###PAGE_SECTION###', $pageSections);
        /** @var array $markerArray */
        $markerArray = [];
        /** @var array $validEmailList */
        $validEmailList = [];
        /** @var bool $sendEmail */
        $sendEmail = true;
        $markerArray['totalBrokenLink'] = $this->totalBrokenLink;
        $markerArray['totalBrokenLink_old'] = $this->oldTotalBrokenLink;
        // Hook
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['reportEmailMarkers'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['reportEmailMarkers'] as $userFunc) {
                $params = [
                    'pObj' => &$this,
                    'markerArray' => $markerArray
                ];
                $newMarkers = GeneralUtility::callUserFunction($userFunc, $params, $this);
                if (is_array($newMarkers)) {
                    $markerArray = $newMarkers + $markerArray;
                }
                unset($params);
            }
        }
        $content = $this->templateService->substituteMarkerArray($content, $markerArray, '###|###', true, true);
        /** @var $mail MailMessage */
        $mail = GeneralUtility::makeInstance(MailMessage::class);
        if (empty($modTsConfig['mail.']['fromemail'])) {
            $modTsConfig['mail.']['fromemail'] = MailUtility::getSystemFromAddress();
        }
        if (empty($modTsConfig['mail.']['fromname'])) {
            $modTsConfig['mail.']['fromname'] = MailUtility::getSystemFromName();
        }
        if (GeneralUtility::validEmail($modTsConfig['mail.']['fromemail'])) {
            $mail->setFrom([$modTsConfig['mail.']['fromemail'] => $modTsConfig['mail.']['fromname']]);
        } else {
            throw new \Exception($this->getLanguageService()->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.error.invalidFromEmail'), '1295476760');
        }
        if (GeneralUtility::validEmail($modTsConfig['mail.']['replytoemail'])) {
            $mail->setReplyTo([$modTsConfig['mail.']['replytoemail'] => $modTsConfig['mail.']['replytoname']]);
        }
        if (!empty($modTsConfig['mail.']['subject'])) {
            $mail->setSubject($modTsConfig['mail.']['subject']);
        } else {
            throw new \Exception($this->getLanguageService()->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.error.noSubject'), '1295476808');
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
                    throw new \Exception($this->getLanguageService()->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.error.invalidToEmail'), '1295476821');
                } else {
                    $validEmailList[] = $emailAdd;
                }
            }
        }
        if (is_array($validEmailList) && !empty($validEmailList)) {
            $mail->setTo($validEmailList);
        } else {
            $sendEmail = false;
        }
        if ($sendEmail) {
            $mail->setBody($content, 'text/html');
            $mail->send();
        }
        return $sendEmail;
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
        // Hook
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['buildMailMarkers'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['buildMailMarkers'] as $userFunc) {
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
        }
        if (is_array($markerArray)) {
            foreach ($markerArray as $markerKey => $markerValue) {
                if (empty($oldBrokenLink[$markerKey])) {
                    $oldBrokenLink[$markerKey] = 0;
                }
                if ($markerValue != $oldBrokenLink[$markerKey]) {
                    $this->isDifferentToLastRun = true;
                }
                $markerArray[$markerKey . '_old'] = $oldBrokenLink[$markerKey];
            }
        }
        $markerArray['title'] = BackendUtility::getRecordTitle(
            'pages',
            BackendUtility::getRecord('pages', $curPage)
        );
        $content = '';
        if ($markerArray['brokenlinkCount'] > 0) {
            $content = $this->templateService->substituteMarkerArray($pageSectionHtml, $markerArray, '###|###', true, true);
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
        $additionalInformation[] = $lang->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.page') . ': ' . $pageLabel;

        $depth = (int)$this->getDepth();
        $additionalInformation[] = $lang->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.depth') . ': ' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_' . ($depth === 999 ? 'infi' : $depth));

        $additionalInformation[] = $lang->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.email') . ': ' . $this->getEmail();

        return implode(', ', $additionalInformation);
    }

    /**
     * Simulate cli call with setting the required options to the $_SERVER['argv']
     *
     * @return void
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

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
