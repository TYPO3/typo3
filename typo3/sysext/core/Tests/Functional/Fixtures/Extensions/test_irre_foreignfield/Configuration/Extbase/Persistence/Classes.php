<?php

declare(strict_types=1);
use TYPO3\TestIrreForeignfield\Domain\Model\Content;
use TYPO3\TestIrreForeignfield\Domain\Model\Hotel;
use TYPO3\TestIrreForeignfield\Domain\Model\Offer;
use TYPO3\TestIrreForeignfield\Domain\Model\Price;

return [
    Content::class => [
        'tableName' => 'tt_content',
        'properties' => [
            'hotels' => [
                'fieldName' => 'tx_testirreforeignfield_hotels',
            ],
        ],
    ],
    Hotel::class => [
        'tableName' => 'tx_testirreforeignfield_hotel',
    ],
    Offer::class => [
        'tableName' => 'tx_testirreforeignfield_offer',
    ],
    Price::class => [
        'tableName' => 'tx_testirreforeignfield_price',
    ],
];
