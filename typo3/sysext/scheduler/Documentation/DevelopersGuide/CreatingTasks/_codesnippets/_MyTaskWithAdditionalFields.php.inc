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
     * Set field values from associative array.
     *
     * @param array $parameters Values from TCA fields
     */
    public function setTaskParameters(array $parameters): void
    {
        $this->myField = $parameters['my_extension_field'] ?? '';
        $this->emailList = $parameters['my_extension_email_list'] ?? '';
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
        return sprintf(
            'Field: %s, Emails: %s',
            $this->myField,
            $this->emailList
        );
    }
}
