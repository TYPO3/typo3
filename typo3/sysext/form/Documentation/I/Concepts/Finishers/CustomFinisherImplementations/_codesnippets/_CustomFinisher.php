<?php

declare(strict_types=1);

namespace MyVendor\MySitePackage\Domain\Finishers;

class CustomFinisher extends \TYPO3\CMS\Form\Domain\Finishers\AbstractFinisher
{
    protected $defaultOptions = [
        'yourCustomOption' => 'Olli',
    ];

    // ...
    protected function executeInternal()
    {
        // TODO: Implement executeInternal() method.
    }
}
