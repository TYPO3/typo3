<?php

return [
    'testStack' => [
        'firstMiddleware' => [
            'disabled' => true,
        ],
        'secondMiddleware' => [
            'target' => 'anotherClassName',
        ],
    ]
];
