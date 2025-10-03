<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Task;

use MyVendor\MyExtension\BusinessLogic;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

final class MyTask extends AbstractTask
{
    protected string $myField = '';
    protected string $emailList = '';


    public function execute(): bool
    {
        # Dependency injection cannot be used in scheduler tasks
        $businessLogic = GeneralUtility::makeInstance(BusinessLogic::class);
        return $businessLogic->run($this->myField, $this->emailList, '…');
    }

    /**
     * Return current field values as associative array.
     * This method is called during migration from old serialized tasks
     * and when displaying task information.
     */
    public function getTaskParameters(): array
    {
        return [
            'my_extension_field' => $this->myField,
            'my_extension_email_list' => $this->emailList,
        ];
    }
    /**
     * Set field values from associative array.
     * This method handles both old and new parameter formats for migration.
     *
     * @param array $parameters Values from either old AdditionalFieldProvider or new TCA fields
     */
    public function setTaskParameters(array $parameters): void
    {
        // Handle migration: check old parameter names first, then new TCA field names
        $this->myField = $parameters['myField'] ?? $parameters['my_extension_field'] ?? '';
        $this->emailList = $parameters['emailList'] ?? $parameters['my_extension_email_list'] ?? '';
    }

    /**
     * Validate task parameters.
     * Only implement this method for validation that cannot be handled by FormEngine.
     * Basic validation like 'required' should be done via TCA 'eval' configuration.
     */
    public function validateTaskParameters(array $parameters): bool
    {
        $isValid = true;

        // Example: Custom email validation (beyond basic 'required' check)
        $emailList = $parameters['my_extension_email_list'] ?? '';
        if (!empty($emailList)) {
            $emails = GeneralUtility::trimExplode(',', $emailList, true);
            foreach ($emails as $email) {
                if (!GeneralUtility::validEmail($email)) {
                    GeneralUtility::makeInstance(FlashMessageService::class)
                        ->getMessageQueueByIdentifier()
                        ->addMessage(
                            GeneralUtility::makeInstance(
                                FlashMessage::class,
                                'Invalid email address: ' . $email,
                                '',
                                ContextualFeedbackSeverity::ERROR
                            )
                        );
                    $isValid = false;
                }
            }
        }

        return $isValid;
    }
    public function getAdditionalInformation(): string
    {
        $info = [];
        if ($this->myField !== '') {
            $info[] = 'Field: ' . $this->myField;
        }
        if ($this->emailList !== '') {
            $info[] = 'Emails: ' . $this->emailList;
        }
        return implode(', ', $info);
    }

    /**
     * is called by additional field provider
     * Todo: Remove setters once TYPO3 13 support is dropped
     * @deprecated
     */
    public function setMyField(string $myField): void
    {
        $this->myField = $myField;
    }

    /**
     * is called by additional field provider
     * Todo: Remove setters once TYPO3 13 support is dropped
     * @deprecated
     */
    public function setEmailList(string $emailList): void
    {
        $this->emailList = $emailList;
    }

}
