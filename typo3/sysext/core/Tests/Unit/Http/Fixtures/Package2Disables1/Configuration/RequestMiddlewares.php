<?php

declare(strict_types=1);

return [
    'testStack' => [
        'firstMiddleware' => [
            'disabled' => true,
        ],
        'secondMiddleware' => [
            'target' => 'anotherClassName',
        ],
    ],
];
