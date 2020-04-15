<?php

declare(strict_types=1);
use OliverHader\IrreTutorial\Domain\Model\Content;
use OliverHader\IrreTutorial\Domain\Model\Hotel;
use OliverHader\IrreTutorial\Domain\Model\Offer;
use OliverHader\IrreTutorial\Domain\Model\Price;

return [
    Content::class => [
        'tableName' => 'tt_content',
        'properties' => [
            'hotels' => [
                'fieldName' => 'tx_irretutorial_1nff_hotels'
            ],
        ]
    ],
    Hotel::class => [
        'tableName' => 'tx_irretutorial_1nff_hotel',
    ],
    Offer::class => [
        'tableName' => 'tx_irretutorial_1nff_offer',
    ],
    Price::class => [
        'tableName' => 'tx_irretutorial_1nff_price',
    ],
];
