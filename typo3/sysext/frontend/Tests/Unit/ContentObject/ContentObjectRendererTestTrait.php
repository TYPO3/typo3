<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject;

use TYPO3\CMS\Core\Site\Entity\Site;

/**
 * Test case
 */
trait ContentObjectRendererTestTrait
{
    /**
     * @return array
     */
    private function getLibParseFunc_RTE(): array
    {
        return [
            'parseFunc' => '',
            'parseFunc.' => [
                'allowTags' => 'a, abbr, acronym, address, article, aside, b, bdo, big, blockquote, br, caption, center, cite, code, col, colgroup, dd, del, dfn, dl, div, dt, em, font, footer, header, h1, h2, h3, h4, h5, h6, hr, i, img, ins, kbd, label, li, link, meta, nav, ol, p, pre, q, samp, sdfield, section, small, span, strike, strong, style, sub, sup, table, thead, tbody, tfoot, td, th, tr, title, tt, u, ul, var',
                'constants' => '1',
                'denyTags' => '*',
                'externalBlocks' => 'article, aside, blockquote, div, dd, dl, footer, header, nav, ol, section, table, ul, pre',
                'externalBlocks.' => [
                    'article.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'aside.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'blockquote.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'dd.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'div.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'dl.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'footer.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'header.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'nav.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'ol.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'section.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                    'table.' => [
                        'HTMLtableCells' => '1',
                        'HTMLtableCells.' => [
                            'addChr10BetweenParagraphs' => '1',
                            'default.' => [
                                'stdWrap.' => [
                                    'parseFunc' => '=< lib.parseFunc_RTE',
                                    'parseFunc.' => [
                                        'nonTypoTagStdWrap.' => [
                                            'encapsLines.' => [
                                                'nonWrappedTag' => '',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'stdWrap.' => [
                            'HTMLparser' => '1',
                            'HTMLparser.' => [
                                'keepNonMatchedTags' => '1',
                                'tags.' => [
                                    'table.' => [
                                        'fixAttrib.' => [
                                            'class.' => [
                                                'always' => '1',
                                                'default' => 'contenttable',
                                                'list' => 'contenttable',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'stripNL' => '1',
                    ],
                    'ul.' => [
                        'callRecursive' => '1',
                        'stripNL' => '1',
                    ],
                ],
                'makelinks' => '1',
                'makelinks.' => [
                    'http.' => [
                        'extTarget.' => [
                            'override' => '_blank',
                        ],
                        'keep' => 'path',
                    ],
                ],
                'nonTypoTagStdWrap.' => [
                    'encapsLines.' => [
                        'addAttributes.' => [
                            'P.' => [
                                'class' => 'bodytext',
                                'class.' => [
                                    'setOnly' => 'blank',
                                ],
                            ],
                        ],
                        'encapsTagList' => 'p,pre,h1,h2,h3,h4,h5,h6,hr,dt,li',
                        'innerStdWrap_all.' => [
                            'ifBlank' => '&nbsp;',
                        ],
                        'nonWrappedTag' => 'P',
                        'remapTag.' => [
                            'DIV' => 'P',
                        ],
                    ],
                    'HTMLparser' => '1',
                    'HTMLparser.' => [
                        'htmlSpecialChars' => '2',
                        'keepNonMatchedTags' => '1',
                    ],
                ],
                'sword' => '<span class="csc-sword">|</span>',
                'tags.' => [
                    'link' => 'TEXT',
                    'link.' => [
                        'current' => '1',
                        'parseFunc.' => [
                            'constants' => '1',
                        ],
                        'typolink.' => [
                            'directImageLink' => false,
                            'extTarget.' => [
                                'override' => '',
                            ],
                            'parameter.' => [
                                'data' => 'parameters : allParams',
                            ],
                            'target.' => [
                                'override' => '',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param array $languageConfiguration
     * @return Site
     */
    private function createSiteWithLanguage(array $languageConfiguration): Site
    {
        return new Site('test', 1, [
            'identifier' => 'test',
            'rootPageId' => 1,
            'base' => '/',
            'languages' => [
                array_merge(
                    $languageConfiguration,
                    [
                        'base' => '/',
                    ]
                )
            ]
        ]);
    }
}
