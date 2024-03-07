<?php

namespace TYPO3\CMS\Resources\Domain;

interface NamespacedReferenceInterface extends ReferenceInterface
{
    public function getNamespace(): ReferenceInterface;
}
