<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Finishers\ClosureFinisher;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
class SomeClass
{
    private function addDeleteUploadsFinisherWithMessage(FormDefinition $formDefinition, string $message)
    {
        $formDefinition->createFinisher('DeleteUploads');
    }
}
