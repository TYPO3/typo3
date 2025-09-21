<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Finishers\ClosureFinisher;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
class SomeClass
{
    private function addRedirectFinisher(FormDefinition $formDefinition)
    {
        $formDefinition->createFinisher('Redirect', [
            'pageUid' => 1,
            'additionalParameters' => 'param1=value1&param2=value2',
        ]);
    }
}
