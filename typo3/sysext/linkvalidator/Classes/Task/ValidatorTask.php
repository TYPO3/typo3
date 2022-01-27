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

namespace TYPO3\CMS\Linkvalidator\Task;

use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\Mailer;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MailUtility;
use TYPO3\CMS\Fluid\View\TemplatePaths;
use TYPO3\CMS\Linkvalidator\Event\ModifyValidatorTaskEmailEvent;
use TYPO3\CMS\Linkvalidator\Result\LinkAnalyzerResult;
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
     * Specific TSconfig for this task.
     *
     * @var string
     */
    protected $configuration = '';

    /**
     * Template name to be used for the email
     *
     * @var string
     */
    protected $emailTemplateName = '';

    /**
     * Level of pages the task should check
     *
     * @var int
     */
    protected $depth = 0;

    /**
     * UID of the start page for this task
     *
     * @var int
     */
    protected $page = 0;

    /**
     * Languages to check for broken links
     *
     * @var string
     */
    protected $languages = '';

    /**
     * Email address to which an email report is sent
     *
     * @var string
     */
    protected $email = '';

    /**
     * Only send an email, if new broken links were found
     *
     * @var bool
     */
    protected $emailOnBrokenLinkOnly = true;

    /**
     * Default language file of the extension linkvalidator
     *
     * @var string
     */
    protected $languageFile = 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf';

    /**
     * Merged mod TSconfig
     *
     * @var array
     */
    protected $modTSconfig = [];

    /**
     * Defines if the task should be updated as some values have changed during task execution
     *
     * @var bool
     */
    protected $taskNeedsUpdate = false;

    /**
     * Get the value of the protected property email
     *
     * @return string Email address to which an email report is sent
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Set the value of the private property email.
     *
     * @param string $email Email address to which an email report is sent
     * @return ValidatorTask
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get the value of the protected property emailOnBrokenLinkOnly
     *
     * @todo type cast needed for backwards compatibility - should be removed in v12
     * @return bool Whether to send an email, if new broken links were found
     */
    public function getEmailOnBrokenLinkOnly(): bool
    {
        return (bool)$this->emailOnBrokenLinkOnly;
    }

    /**
     * Set the value of the private property emailOnBrokenLinkOnly
     *
     * @param bool $emailOnBrokenLinkOnly Only send an email, if new broken links were found
     * @return ValidatorTask
     */
    public function setEmailOnBrokenLinkOnly(bool $emailOnBrokenLinkOnly): self
    {
        $this->emailOnBrokenLinkOnly = $emailOnBrokenLinkOnly;
        return $this;
    }

    /**
     * Get the value of the protected property page
     *
     * @todo type cast needed for backwards compatibility - should be removed in v12
     * @return int UID of the start page for this task
     */
    public function getPage(): int
    {
        return (int)$this->page;
    }

    /**
     * Set the value of the private property page
     *
     * @param int $page UID of the start page for this task.
     * @return ValidatorTask
     */
    public function setPage(int $page): self
    {
        $this->page = $page;
        return $this;
    }

    /**
     * Get the value of the protected property languages
     *
     * @return string Languages to fetch broken links
     */
    public function getLanguages(): string
    {
        return $this->languages;
    }

    /**
     * Set the value of the private property languages
     *
     * @param string $languages Languages to fetch broken links
     * @return ValidatorTask
     */
    public function setLanguages(string $languages): self
    {
        $this->languages = $languages;
        return $this;
    }

    /**
     * Get the value of the protected property depth
     *
     * @todo type cast needed for backwards compatibility - should be removed in v12
     * @return int Level of pages the task should check
     */
    public function getDepth(): int
    {
        return (int)$this->depth;
    }

    /**
     * Set the value of the private property depth
     *
     * @param int $depth Level of pages the task should check
     * @return ValidatorTask
     */
    public function setDepth(int $depth): self
    {
        $this->depth = $depth;
        return $this;
    }

    /**
     * Get the value of the protected property emailTemplateName
     *
     * @return string Template name to be used for the email
     */
    public function getEmailTemplateName(): string
    {
        return $this->emailTemplateName;
    }

    /**
     * Set the value of the private property emailTemplateName
     *
     * @param string $emailTemplateName Template name to be used for the email
     * @return ValidatorTask
     */
    public function setEmailTemplateName(string $emailTemplateName): self
    {
        $this->emailTemplateName = $emailTemplateName;
        return $this;
    }

    /**
     * Get the value of the protected property configuration
     *
     * @return string specific TSconfig for this task
     */
    public function getConfiguration(): string
    {
        return $this->configuration;
    }

    /**
     * Set the value of the private property configuration
     *
     * @param string $configuration specific TSconfig for this task
     * @return ValidatorTask
     */
    public function setConfiguration(string $configuration): self
    {
        $this->configuration = $configuration;
        return $this;
    }

    /**
     * Function execute from the Scheduler
     *
     * @return bool TRUE on successful execution, FALSE on error
     */
    public function execute(): bool
    {
        // @todo type cast needed for backwards compatibility - should be removed in v12
        $this->page = (int)$this->page;
        $this->depth = (int)$this->depth;
        $this->emailOnBrokenLinkOnly = (bool)$this->emailOnBrokenLinkOnly;

        if ($this->page === 0) {
            return false;
        }

        $this
            ->setCliArguments()
            ->loadModTSconfig();

        $successfullyExecuted = true;
        $linkAnalyzerResult = $this->getLinkAnalyzerResult();

        if ($linkAnalyzerResult->getTotalBrokenLinksCount() > 0
            && (!$this->emailOnBrokenLinkOnly || $linkAnalyzerResult->isDifferentToLastResult())
        ) {
            $successfullyExecuted = $this->reportEmail($linkAnalyzerResult);
        }

        if ($this->taskNeedsUpdate) {
            $this->taskNeedsUpdate = false;
            $this->save();
        }

        return $successfullyExecuted;
    }

    /**
     * Validate all broken links for pages set in the task configuration
     * and return the analyzers result as object.
     *
     * @return LinkAnalyzerResult
     */
    protected function getLinkAnalyzerResult(): LinkAnalyzerResult
    {
        $pageRow = BackendUtility::getRecord('pages', $this->page, '*', '', false);
        if ($pageRow === null) {
            throw new \InvalidArgumentException(
                sprintf($this->getLanguageService()->sL($this->languageFile . ':tasks.error.invalidPageUid'), $this->page),
                1502800555
            );
        }

        return GeneralUtility::makeInstance(LinkAnalyzerResult::class)
            ->getResultForTask(
                $this->page,
                $this->depth,
                $pageRow,
                $this->modTSconfig,
                $this->getSearchField(),
                $this->getLinkTypes(),
                $this->languages
            );
    }

    /**
     * Load and merge linkvalidator TSconfig from task configuration with page TSconfig
     *
     * @return ValidatorTask
     */
    protected function loadModTSconfig(): self
    {
        $parseObj = GeneralUtility::makeInstance(TypoScriptParser::class);
        $parseObj->parse($this->configuration);
        if (!empty($parseObj->errors)) {
            $parseErrorMessage = $this->getLanguageService()->sL($this->languageFile . ':tasks.error.invalidTSconfig') . '<br />';
            foreach ($parseObj->errors as $errorInfo) {
                $parseErrorMessage .= $errorInfo[0] . '<br />';
            }
            throw new \Exception($parseErrorMessage, 1295476989);
        }
        $modTs = BackendUtility::getPagesTSconfig($this->page)['mod.']['linkvalidator.'] ?? [];
        $overrideTs = $parseObj->setup['mod.']['linkvalidator.'] ?? [];
        if (is_array($overrideTs) && $overrideTs !== []) {
            ArrayUtility::mergeRecursiveWithOverrule($modTs, $overrideTs);
        }
        if (empty($modTs['mail.']['fromemail'])) {
            $modTs['mail.']['fromemail'] = MailUtility::getSystemFromAddress();
        }
        if (empty($modTs['mail.']['fromname'])) {
            $modTs['mail.']['fromname'] = MailUtility::getSystemFromName();
        }

        $this->modTSconfig = $modTs;

        return $this;
    }

    /**
     * Get the list of fields to consider for fetching broken links
     *
     * @return array $searchFields List of search fields
     */
    protected function getSearchField(): array
    {
        $searchFields = [];
        foreach ($this->modTSconfig['searchFields.'] as $table => $fieldList) {
            $fields = GeneralUtility::trimExplode(',', $fieldList);
            foreach ($fields as $field) {
                $searchFields[$table][] = $field;
            }
        }
        return $searchFields;
    }

    /**
     * Get the list of linkTypes to consider for fetching broken links
     *
     * @return array<int, string> $linkTypes list of link types
     */
    protected function getLinkTypes(): array
    {
        $linkTypes = [];
        $typesTmp = GeneralUtility::trimExplode(',', $this->modTSconfig['linktypes'], true);
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['linkvalidator']['checkLinks'] ?? [] as $type => $value) {
            if (in_array($type, $typesTmp, true)) {
                $linkTypes[] = (string)$type;
            }
        }
        return $linkTypes;
    }

    /**
     * Build and send report email when broken links were found
     *
     * @param LinkAnalyzerResult $linkAnalyzerResult
     * @return bool TRUE if mail was sent, FALSE if or not
     */
    protected function reportEmail(LinkAnalyzerResult $linkAnalyzerResult): bool
    {
        $lang = $this->getLanguageService();
        $fluidEmail = $this->getFluidEmail();

        // Initialize and call the event
        $validatorTaskEmailEvent = new ModifyValidatorTaskEmailEvent($linkAnalyzerResult, $fluidEmail, $this->modTSconfig);
        GeneralUtility::makeInstance(EventDispatcherInterface::class)->dispatch($validatorTaskEmailEvent);
        $fluidEmail->assign('linkAnalyzerResult', $linkAnalyzerResult);

        if (!empty($this->modTSconfig['mail.']['subject']) && $fluidEmail->getSubject() === null) {
            $fluidEmail->subject($this->modTSconfig['mail.']['subject']);
        }

        if ($fluidEmail->getFrom() === []) {
            if (GeneralUtility::validEmail($this->modTSconfig['mail.']['fromemail'] ?? '')) {
                $fluidEmail->from(
                    new Address($this->modTSconfig['mail.']['fromemail'], $this->modTSconfig['mail.']['fromname'] ?? '')
                );
            } else {
                throw new \Exception($lang->sL($this->languageFile . ':tasks.error.invalidFromEmail'), 1295476760);
            }
        }

        if ($this->email !== '') {
            $validEmailList = [];

            if (str_contains($this->email, ',')) {
                $emailList = GeneralUtility::trimExplode(',', $this->email, true);
                $this->email = implode(LF, $emailList);
                $this->taskNeedsUpdate = true;
            } else {
                $emailList = GeneralUtility::trimExplode(LF, $this->email, true);
            }

            foreach ($emailList as $email) {
                if (GeneralUtility::validEmail($email)) {
                    $validEmailList[] = $email;
                    continue;
                }
                throw new \Exception($lang->sL($this->languageFile . ':tasks.error.invalidToEmail'), 1295476821);
            }

            $fluidEmail->addTo(...$validEmailList);
        }

        if ($fluidEmail->getTo() === []) {
            throw new \Exception($lang->sL($this->languageFile . ':tasks.error.emptyToEmail'), 1599724418);
        }

        if ($fluidEmail->getReplyTo() === [] &&
            GeneralUtility::validEmail($this->modTSconfig['mail.']['replytoemail'] ?? '')
        ) {
            $fluidEmail->replyTo(
                new Address($this->modTSconfig['mail.']['replytoemail'], $this->modTSconfig['mail.']['replytoname'] ?? '')
            );
        }

        try {
            GeneralUtility::makeInstance(Mailer::class)->send($fluidEmail);
        } catch (TransportExceptionInterface $e) {
            return false;
        }

        return true;
    }

    /**
     * Returns the most important properties of the LinkValidator task as a
     * comma separated string that will be displayed in the scheduler module.
     *
     * @return string
     */
    public function getAdditionalInformation(): string
    {
        $additionalInformation = [];
        $pageLabel = $this->page;
        if ($this->page) {
            $pageData = BackendUtility::getRecord('pages', $this->page);
            if (!empty($pageData)) {
                $pageTitle = BackendUtility::getRecordTitle('pages', $pageData);
                $pageLabel = $pageTitle . ' (' . $this->page . ')';
            }
        }
        $lang = $this->getLanguageService();
        $depth = $this->depth;
        $additionalInformation[] = $lang->sL($this->languageFile . ':tasks.validate.page') . ': ' . $pageLabel;
        $additionalInformation[] = $lang->sL($this->languageFile . ':tasks.validate.depth') . ': '
            . $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_' . ($depth === 999 ? 'infi' : $depth));
        $additionalInformation[] = $lang->sL($this->languageFile . ':tasks.validate.email') . ': '
            . $this->getEmail();

        return implode(', ', $additionalInformation);
    }

    /**
     * Simulate cli call with setting the required options to the $_SERVER['argv']
     *
     * @return ValidatorTask
     */
    protected function setCliArguments(): self
    {
        $_SERVER['argv'] = [
            ($_SERVER['argv'][0] ?? null),
            'tx_link_scheduler_link',
            '0',
            '-ss',
            '--sleepTime',
            $this->sleepTime,
            '--sleepAfterFinish',
            $this->sleepAfterFinish,
            '--countInARun',
            $this->countInARun,
        ];

        return $this;
    }

    /**
     * Get FluidEmail with template from the task configuration
     *
     * @return FluidEmail
     */
    protected function getFluidEmail(): FluidEmail
    {
        $templateConfiguration = array_replace_recursive(
            $GLOBALS['TYPO3_CONF_VARS']['MAIL'],
            ['templateRootPaths' => [20 => 'EXT:linkvalidator/Resources/Private/Templates/Email/']]
        );

        // must be sorted after adding the default path to ensure already registered custom paths are called first
        ksort($templateConfiguration['templateRootPaths']);
        $templatePaths = GeneralUtility::makeInstance(TemplatePaths::class, $templateConfiguration);

        if ($this->emailTemplateName === '' || !$this->templateFilesExist($templatePaths->getTemplateRootPaths())) {
            // Add default template name to task if empty or given template name does not exist
            $this->emailTemplateName = 'ValidatorTask';
            $this->taskNeedsUpdate = true;
            $this->logger->notice($this->getLanguageService()->sL($this->languageFile . ':tasks.notice.useDefaultTemplate'));
        }

        $fluidEmail = GeneralUtility::makeInstance(FluidEmail::class, $templatePaths);
        $fluidEmail->setTemplate($this->emailTemplateName);

        return $fluidEmail;
    }

    /**
     * Check if both template files (html and txt) exist under at least one template path
     *
     * @param array $templatePaths
     * @return bool
     */
    protected function templateFilesExist(array $templatePaths): bool
    {
        foreach ($templatePaths as $templatePath) {
            if (file_exists($templatePath . $this->emailTemplateName . '.html')
                && file_exists($templatePath . $this->emailTemplateName . '.txt')
            ) {
                return true;
            }
        }
        return false;
    }
}
