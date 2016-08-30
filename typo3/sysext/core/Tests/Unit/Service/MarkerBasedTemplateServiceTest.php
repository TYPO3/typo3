<?php
namespace TYPO3\CMS\Core\Tests\Unit\Service;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Unit test for marker utility
 */
class MarkerBasedTemplateServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var MarkerBasedTemplateService
     */
    protected $templateService;

    protected function setUp()
    {
        $this->templateService = GeneralUtility::makeInstance(MarkerBasedTemplateService::class);
    }

    /**
     * Data provider for getSubpart
     *
     * @return array
     */
    public function getSubpartDataProvider()
    {
        return [
            'No start marker' => [
                '<body>text</body>',
                '###SUBPART###',
                ''
            ],
            'No stop marker' => [
                '<body>
<!-- ###SUBPART### Start -->
text
</body>',
                '###SUBPART###',
                ''
            ],
            'Start and stop marker in HTML comment' => [
                '<body>
<!-- ###SUBPART### Start -->
text
<!-- ###SUBPART### End -->
</body>',
                '###SUBPART###',
                '
text
'
            ],
            'Stop marker in HTML comment' => [
                '<body>
###SUBPART###
text
<!-- ###SUBPART### End -->
</body>',
                '###SUBPART###',
                '
text
'
            ],
            'Start marker in HTML comment' => [
                '<body>
<!-- ###SUBPART### Start -->
text
###SUBPART###
</body>',
                '###SUBPART###',
                '
text
'
            ],
            'Start and stop marker direct' => [
                '<body>
###SUBPART###
text
###SUBPART###
</body>',
                '###SUBPART###',
                '
text
'
            ],
        ];
    }

    /**
     * @test
     * @param string $content
     * @param string $marker
     * @param string $expected
     * @dataProvider getSubpartDataProvider
     */
    public function getSubpart($content, $marker, $expected)
    {
        $this->assertSame($expected, $this->templateService->getSubpart($content, $marker));
    }

    /**
     * Data provider for substituteSubpart
     *
     * @return array
     */
    public function substituteSubpartDataProvider()
    {
        return [
            'No start marker' => [
                '<body>text</body>',
                '###SUBPART###',
                'hello',
                false,
                false,
                '<body>text</body>'
            ],
            'No stop marker' => [
                '<body>
<!-- ###SUBPART### Start -->
text
</body>',
                '###SUBPART###',
                'hello',
                false,
                false,
                '<body>
<!-- ###SUBPART### Start -->
text
</body>',
            ],
            'Start and stop marker in HTML comment' => [
                '<body>
<!-- ###SUBPART### Start -->
text
<!-- ###SUBPART### End -->
</body>',
                '###SUBPART###',
                'hello',
                false,
                false,
                '<body>
hello
</body>'
            ],
            'Recursive subpart' => [
                '<body>
<!-- ###SUBPART### Start -->text1<!-- ###SUBPART### End -->
<!-- ###SUBPART### Start -->text2<!-- ###SUBPART### End -->
</body>',
                '###SUBPART###',
                'hello',
                true,
                false,
                '<body>
hello
hello
</body>'
            ],
            'Keep HTML marker' => [
                '<body>
<!-- ###SUBPART### Start -->text<!-- ###SUBPART### End -->
</body>',
                '###SUBPART###',
                'hello',
                false,
                true,
                '<body>
<!-- ###SUBPART### Start -->hello<!-- ###SUBPART### End -->
</body>'
            ],
            'Keep HTML begin marker' => [
                '<body>
<!-- ###SUBPART### Start -->text###SUBPART###
</body>',
                '###SUBPART###',
                'hello',
                false,
                true,
                '<body>
<!-- ###SUBPART### Start -->hello###SUBPART###
</body>'
            ],
            'Keep HTML end marker' => [
                '<body>
###SUBPART###text<!-- ###SUBPART### End -->
</body>',
                '###SUBPART###',
                'hello',
                false,
                true,
                '<body>
###SUBPART###hello<!-- ###SUBPART### End -->
</body>'
            ],
            'Keep plain marker' => [
                '<body>
###SUBPART###text###SUBPART###
</body>',
                '###SUBPART###',
                'hello',
                false,
                true,
                '<body>
###SUBPART###hello###SUBPART###
</body>'
            ],
            'Wrap around' => [
                '<body>
###SUBPART###text###SUBPART###
</body>',
                '###SUBPART###',
                ['before-', '-after'],
                false,
                true,
                '<body>
###SUBPART###before-text-after###SUBPART###
</body>'
            ],
        ];
    }

    /**
     * @test
     * @param string $content
     * @param string $marker
     * @param array $subpartContent
     * @param bool $recursive
     * @param bool $keepMarker
     * @param string $expected
     * @dataProvider substituteSubpartDataProvider
     */
    public function substituteSubpart($content, $marker, $subpartContent, $recursive, $keepMarker, $expected)
    {
        $this->assertSame($expected, $this->templateService->substituteSubpart($content, $marker, $subpartContent, $recursive, $keepMarker));
    }

    /**
     * Data provider for substituteMarkerArray
     */
    public function substituteMarkerArrayDataProvider()
    {
        return [
            'Upper case marker' => [
                'This is ###MARKER1### and this is ###MARKER2###',
                ['###MARKER1###' => 'marker 1',
                    '###MARKER2###' => 'marker 2'],
                '',
                false,
                false,
                'This is marker 1 and this is marker 2'
            ],
            'Lower case marker' => [
                'This is ###MARKER1### and this is ###MARKER2###',
                ['###marker1###' => 'marker 1',
                    '###marker2###' => 'marker 2'],
                '',
                true,
                false,
                'This is marker 1 and this is marker 2'
            ],
            'Upper case marker without hash mark' => [
                'This is ###MARKER1### and this is ###MARKER2###',
                ['MARKER1' => 'marker 1',
                    'MARKER2' => 'marker 2'],
                '###|###',
                false,
                false,
                'This is marker 1 and this is marker 2'
            ],
            'Upper case marker with another hash mark' => [
                'This is *MARKER1* and this is *MARKER2*',
                ['MARKER1' => 'marker 1',
                    'MARKER2' => 'marker 2'],
                '*|*',
                false,
                false,
                'This is marker 1 and this is marker 2'
            ],
            'Upper case marker with unused marker' => [
                'This is ###MARKER1### and this is ###MARKER2### ###UNUSED###',
                ['###MARKER1###' => 'marker 1',
                    '###MARKER2###' => 'marker 2'],
                '',
                false,
                false,
                'This is marker 1 and this is marker 2 ###UNUSED###'
            ],
            'Upper case marker with unused marker deleted' => [
                'This is ###MARKER1### and this is ###MARKER2### ###UNUSED###',
                ['###MARKER1###' => 'marker 1',
                    '###MARKER2###' => 'marker 2'],
                '',
                false,
                true,
                'This is marker 1 and this is marker 2 '
            ],
        ];
    }

    /**
     * @test
     * @dataProvider substituteMarkerArrayDataProvider
     * @param string $content The content stream, typically HTML template content.
     * @param array $markContentArray The array of key/value pairs being marker/content values used in the substitution. For each element in this array the function will substitute a marker in the content stream with the content.
     * @param string $wrap A wrap value - [part 1] | [part 2] - for the markers before substitution
     * @param bool $uppercase If set, all marker string substitution is done with upper-case markers.
     * @param bool $deleteUnused If set, all unused marker are deleted.
     * @param string $expected
     */
    public function substituteMarkerArray($content, $markContentArray, $wrap, $uppercase, $deleteUnused, $expected)
    {
        $this->assertSame($expected, $this->templateService->substituteMarkerArray($content, $markContentArray, $wrap, $uppercase, $deleteUnused));
    }

    /**
     * Data provider for substituteMarker
     */
    public function substituteMarkerDataProvider()
    {
        return [
            'Single marker' => [
                'This is a ###SAMPLE### text',
                '###SAMPLE###',
                'simple',
                'This is a simple text'
            ],
            'Double marker' => [
                'This is a ###SAMPLE### text with a ###SAMPLE### content',
                '###SAMPLE###',
                'simple',
                'This is a simple text with a simple content'
            ],
        ];
    }

    /**
     * @dataProvider substituteMarkerDataProvider
     * @param string $content The content stream, typically HTML template content.
     * @param string $marker The marker string, typically on the form "###[the marker string]###
     * @param mixed $markContent The content to insert instead of the marker string found.
     * @param string $expected The expected result of the substitution
     */
    public function substituteMarker($content, $marker, $markContent, $expected)
    {
        $this->assertSame($expected, $this->templateService->substituteMarker($content, $marker, $markContent));
    }

    /**
     * Data provider for substituteSubpartArray
     *
     * @return array
     */
    public function substituteSubpartArrayDataProvider()
    {
        return [
            'Substitute multiple subparts at once with plain marker' => [
                '<body>
###SUBPART1###text1###SUBPART1###
###SUBPART2###text2###SUBPART2###
</body>',
                ['###SUBPART1###' => 'hello',
                    '###SUBPART2###' => 'world'],
                '<body>
hello
world
</body>'
            ],
        ];
    }

    /**
     * @test
     * @param string $content
     * @param array $subpartsContent
     * @param string $expected
     * @dataProvider substituteSubpartArrayDataProvider
     */
    public function substituteSubpartArray($content, array $subpartsContent, $expected)
    {
        $this->assertSame($expected, $this->templateService->substituteSubpartArray($content, $subpartsContent));
    }

    /**
     * Data provider for substituteMarkerAndSubpartArrayRecursiveResolvesMarkersAndSubpartsArray
     *
     * @return array
     */
    public function substituteMarkerAndSubpartArrayRecursiveResolvesMarkersAndSubpartsArrayDataProvider()
    {
        $template = '###SINGLEMARKER1###
<!-- ###FOO### begin -->
<!-- ###BAR### begin -->
###SINGLEMARKER2###
<!-- ###BAR### end -->
<!-- ###FOOTER### begin -->
###SINGLEMARKER3###
<!-- ###FOOTER### end -->
<!-- ###FOO### end -->';

        $expected ='Value 1


Value 2.1

Value 2.2


Value 3.1

Value 3.2

';

        return [
            'Single marker' => [
                '###SINGLEMARKER###',
                [
                    '###SINGLEMARKER###' => 'Value 1'
                ],
                '',
                false,
                false,
                'Value 1'
            ],
            'Subpart marker' => [
                $template,
                [
                    '###SINGLEMARKER1###' => 'Value 1',
                    '###FOO###' => [
                        [
                            '###BAR###' => [
                                [
                                    '###SINGLEMARKER2###' => 'Value 2.1'
                                ],
                                [
                                    '###SINGLEMARKER2###' => 'Value 2.2'
                                ]
                            ],
                            '###FOOTER###' => [
                                [
                                    '###SINGLEMARKER3###' => 'Value 3.1'
                                ],
                                [
                                    '###SINGLEMARKER3###' => 'Value 3.2'
                                ]
                            ]
                        ]
                    ]
                ],
                '',
                false,
                false,
                $expected
            ],
            'Subpart marker with wrap' => [
                $template,
                [
                    'SINGLEMARKER1' => 'Value 1',
                    'FOO' => [
                        [
                            'BAR' => [
                                [
                                    'SINGLEMARKER2' => 'Value 2.1'
                                ],
                                [
                                    'SINGLEMARKER2' => 'Value 2.2'
                                ]
                            ],
                            'FOOTER' => [
                                [
                                    'SINGLEMARKER3' => 'Value 3.1'
                                ],
                                [
                                    'SINGLEMARKER3' => 'Value 3.2'
                                ]
                            ]
                        ]
                    ]
                ],
                '###|###',
                false,
                false,
                $expected
            ],
            'Subpart marker with lower marker array keys' => [
                $template,
                [
                    '###singlemarker1###' => 'Value 1',
                    '###foo###' => [
                        [
                            '###bar###' => [
                                [
                                    '###singlemarker2###' => 'Value 2.1'
                                ],
                                [
                                    '###singlemarker2###' => 'Value 2.2'
                                ]
                            ],
                            '###footer###' => [
                                [
                                    '###singlemarker3###' => 'Value 3.1'
                                ],
                                [
                                    '###singlemarker3###' => 'Value 3.2'
                                ]
                            ]
                        ]
                    ]
                ],
                '',
                true,
                false,
                $expected
            ],
            'Subpart marker with unused markers' => [
                $template,
                [
                    '###FOO###' => [
                        [
                            '###BAR###' => [
                                [
                                    '###SINGLEMARKER2###' => 'Value 2.1'
                                ]
                            ],
                            '###FOOTER###' => [
                                [
                                    '###SINGLEMARKER3###' => 'Value 3.1'
                                ]
                            ]
                        ]
                    ]
                ],
                '',
                false,
                true,
                '


Value 2.1


Value 3.1

'
            ],
            'Subpart marker with empty subpart' => [
                $template,
                [
                    '###SINGLEMARKER1###' => 'Value 1',
                    '###FOO###' => [
                        [
                            '###BAR###' => [
                                [
                                    '###SINGLEMARKER2###' => 'Value 2.1'
                                ],
                                [
                                    '###SINGLEMARKER2###' => 'Value 2.2'
                                ]
                            ],
                            '###FOOTER###' => []
                        ]
                    ]
                ],
                '',
                false,
                false,
                'Value 1


Value 2.1

Value 2.2


'
            ]
        ];
    }

    /**
     * @test
     * @param string $template
     * @param array $markersAndSubparts
     * @param string $wrap
     * @param bool $uppercase
     * @param bool $deleteUnused
     * @param string $expected
     * @dataProvider substituteMarkerAndSubpartArrayRecursiveResolvesMarkersAndSubpartsArrayDataProvider
     */
    public function substituteMarkerAndSubpartArrayRecursiveResolvesMarkersAndSubpartsArray($template, $markersAndSubparts, $wrap, $uppercase, $deleteUnused, $expected)
    {
        $this->assertSame($expected, $this->templateService->substituteMarkerAndSubpartArrayRecursive($template, $markersAndSubparts, $wrap, $uppercase, $deleteUnused));
    }
}
