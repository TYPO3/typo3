<?php

use TYPO3\CMS\Form\Domain\Model\FormDefinition;

class SomeClass
{
    private function addDeleteUploadsFinisherWithMessage(FormDefinition $formDefinition, string $message)
    {
        $formDefinition->createFinisher('DeleteUploads');
    }
}
