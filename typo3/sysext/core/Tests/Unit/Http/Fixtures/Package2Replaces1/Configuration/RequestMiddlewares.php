<?php

declare(strict_types=1);

return [
    'testStack' => [
        'firstMiddleware' => [
            'target' => 'replacedClassName',
        ],
        'secondMiddleware' => [
            'target' => 'anotherClassName',
        ],
    ],
];
