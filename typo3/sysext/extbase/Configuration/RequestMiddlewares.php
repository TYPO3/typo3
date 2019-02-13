<?php

return [
    'backend' => [
        'typo3/cms-extbase/signal-slot-deprecator' => [
            'target' => \TYPO3\CMS\Extbase\Middleware\SignalSlotDeprecator::class,
            'after' => [
                'typo3/cms-core/normalized-params-attribute'
            ],
        ],
    ]
];
