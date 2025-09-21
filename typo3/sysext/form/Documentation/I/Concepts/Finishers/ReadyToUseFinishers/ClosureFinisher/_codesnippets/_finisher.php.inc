<?php

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Finishers\ClosureFinisher;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;

class SomeClass
{
    private function addClosureFinisher(FormDefinition $formDefinition)
    {

        $closureFinisher = GeneralUtility::makeInstance(ClosureFinisher::class);
        $closureFinisher->setOption('closure', function ($finisherContext)
        {
            $formRuntime = $finisherContext->getFormRuntime();
            // ...
        });
        $formDefinition->addFinisher($closureFinisher);
    }
}
