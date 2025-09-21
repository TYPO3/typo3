<?php

use TYPO3\CMS\Form\Domain\Model\FormDefinition;
class SomeClass
{
    private function addConfirmationnFinisherWithMessage(FormDefinition $formDefinition, string $message)
    {
        $formDefinition->createFinisher('Confirmation', [
            'message' => $message,
        ]);
    }
    private function addConfirmationnFinisherWithContentElement(FormDefinition $formDefinition, int $contentElementUid)
    {
        $formDefinition->createFinisher('Confirmation', [
            'contentElementUid' => $contentElementUid,
        ]);
    }
}
