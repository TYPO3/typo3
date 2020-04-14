<?php

declare(strict_types=1);

return [
    \OliverHader\IrreTutorial\Domain\Model\Content::class => [
        'tableName' => 'tt_content',
        'properties' => [
            'hotels' => [
                'fieldName' => 'tx_irretutorial_1nff_hotels'
            ],
        ]
    ],
    \OliverHader\IrreTutorial\Domain\Model\Hotel::class => [
        'tableName' => 'tx_irretutorial_1nff_hotel',
    ],
    \OliverHader\IrreTutorial\Domain\Model\Offer::class => [
        'tableName' => 'tx_irretutorial_1nff_offer',
    ],
    \OliverHader\IrreTutorial\Domain\Model\Price::class => [
        'tableName' => 'tx_irretutorial_1nff_price',
    ],
];
