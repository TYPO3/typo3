<?php
return [
    '7' => [
        'releases' => [
            '7.4-dev' => [
                'type' => 'development',
                'date' => '2014-06-01 18:24:25 UTC',
            ],
            '7.3.1' => [
                'type' => 'regular',
                'date' => '2014-05-01 18:24:25 UTC',
            ],
            '7.3.0' => [
                'type' => 'security',
                'date' => '2014-04-01 18:24:25 UTC',
            ],
            '7.2.0' => [
                'type' => 'regular',
                'date' => '2014-03-01 18:24:25 UTC',
                'checksums' => [
                    'tar' => [
                        'md5' => 'e91acf53bb03cb943bd27e76643901c5',
                        'sha1' => '3dc156eed4b99577232f537d798a8691493f8a83',
                    ],
                    'zip' => [
                        'md5' => 'f8d166e9979a43490ec0ae03e0ff46a1',
                        'sha1' => '87448a8745b6eae36bd1e7cb6705a42771edfa03',
                    ],
                ],
                'url' => [
                    'zip' => 'http://get.typo3.org/7.2/zip',
                    'tar' => 'http://get.typo3.org/7.2',
                ],
            ],
        ],
        'latest' => '7.3.1',
        'stable' => '7.3.1',
        'active' => true,
    ],
];
