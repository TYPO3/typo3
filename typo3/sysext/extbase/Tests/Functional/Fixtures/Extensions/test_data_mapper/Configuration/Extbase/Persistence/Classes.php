<?php

declare(strict_types=1);

use TYPO3Tests\TestDataMapper\Domain\Model\CapabilitiesValueObject;
use TYPO3Tests\TestDataMapper\Domain\Model\ConstructorPromotionExample;
use TYPO3Tests\TestDataMapper\Domain\Model\ExplicitSystemCapabilities;
use TYPO3Tests\TestDataMapper\Domain\Model\SystemCapabilities;

return [
    ConstructorPromotionExample::class => [
        'tableName' => 'tx_testdatamapper_domain_model_constructor_promotion_example',
    ],
    SystemCapabilities::class => [
        'tableName' => 'tx_testdatamapper_domain_model_system_capabilities',
    ],
    ExplicitSystemCapabilities::class => [
        'tableName' => 'tx_testdatamapper_domain_model_explicit_system_capabilities',
    ],
    CapabilitiesValueObject::class => [
        'tableName' => 'tx_testdatamapper_domain_model_capabilities_value_object',
    ],
];
