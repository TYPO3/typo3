<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Finishers\ClosureFinisher;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
class SomeClass
{
    private function addEmailToReceiverFinisher(FormDefinition $formDefinition)
    {
        $formDefinition->createFinisher('EmailToReceiver', [
            'subject' => 'Your message',
            'recipients' => [
                'your.company@example.com' => 'Your Company name',
                'ceo@example.com' => 'CEO'
            ],
            'senderAddress' => $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'],
            'senderName' => $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'],
        ]);
    }
}
