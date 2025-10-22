<?php

use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

class SomeClass
{
    private function addFlashMessageFinisher(FormDefinition $formDefinition, string $message)
    {
        $formDefinition->createFinisher('FlashMessage', [
            'messageTitle' => 'Merci',
            'messageCode' => 201905041245,
            'messageBody' => 'Thx for using %s',
            'messageArguments' => ['TYPO3'],
            'severity' => ContextualFeedbackSeverity::OK,
        ]);
    }
}
