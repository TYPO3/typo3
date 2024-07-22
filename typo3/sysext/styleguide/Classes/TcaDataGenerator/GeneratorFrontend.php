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

namespace TYPO3\CMS\Styleguide\TcaDataGenerator;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Styleguide\Service\KauderwelschService;

/**
 * Manage a page tree with all test / demo styleguide data
 *
 * @internal
 */
#[Autoconfigure(public: true)]
final class GeneratorFrontend extends AbstractGenerator
{
    public function __construct(
        private readonly KauderwelschService $kauderwelschService,
        private readonly RecordFinder $recordFinder,
    ) {}

    public function create(string $basePath = '', int $hidden = 1, bool $useSiteSets = false): void
    {
        // Create should not be called if demo frontend data exists already
        if (count($this->recordFinder->findUidsOfFrontendPages())) {
            throw new Exception(
                'Can not create a second styleguide frontend record tree',
                1626357141
            );
        }

        // Add files
        $this->addToFal([
            'bus_lane.jpg',
            'telephone_box.jpg',
            'underground.jpg',
        ], 'EXT:styleguide/Resources/Public/Images/Pictures/', 'styleguide_frontend');

        // Add entry page on top level
        $newIdOfEntryPage = StringUtility::getUniqueId('NEW');
        $newIdOfUserFolder = StringUtility::getUniqueId('NEW');
        $newIdOfEntryContent = StringUtility::getUniqueId('NEW');
        $newIdOfCategory = StringUtility::getUniqueId('NEW');
        $newIdOfFrontendGroup = StringUtility::getUniqueId('NEW');
        $newIdOfFrontendUser = StringUtility::getUniqueId('NEW');

        $data = [
            'pages' => [
                $newIdOfEntryPage => [
                    'title' => 'styleguide frontend demo',
                    'pid' => 0 - $this->getUidOfLastTopLevelPage(),
                    // Define page as styleguide frontend
                    'tx_styleguide_containsdemo' => 'tx_styleguide_frontend_root',
                    'is_siteroot' => 1,
                    'hidden' => $hidden,
                ],
                // Storage for for frontend users
                $newIdOfUserFolder => [
                    'title' => 'frontend user',
                    'pid' => $newIdOfEntryPage,
                    'tx_styleguide_containsdemo' => 'tx_styleguide_frontend',
                    'hidden' => 0,
                    'doktype' => 254,
                ],
            ],
            'tt_content' => [
                $newIdOfEntryContent => [
                    'header' => 'TYPO3 Styleguide Frontend',
                    'CType' => 'text',
                    'bodytext' => 'This is the generated frontend for the Styleguide Extension. This consists of all default content elements of the TYPO3 Core.',
                    'pid' => $newIdOfEntryPage,
                    'tx_styleguide_containsdemo' => 'tx_styleguide_frontend',
                ],
            ],
            'sys_category' => [
                $newIdOfCategory => [
                    'title' => 'Styleguide Demo Category',
                    'pid' => $newIdOfEntryPage,
                ],
            ],
            'fe_groups' => [
                $newIdOfFrontendGroup => [
                    'title' => 'Styleguide Frontend Demo',
                    'hidden' => 0,
                    'pid' => $newIdOfUserFolder,
                    'tx_styleguide_containsdemo' => 'tx_styleguide_frontend',
                ],
            ],
            'fe_users' => [
                $newIdOfFrontendUser => [
                    'username' => 'styleguide-frontend-demo',
                    'hidden' => 0,
                    'usergroups' => $newIdOfFrontendGroup,
                    // Password of demo frontend user: 'password'
                    'password' => '$argon2i$v=19$m=65536,t=16,p=1$VjFaWDFGMmh6RlNEWjY2Vw$Vp5lFrbe8/GNwIrlXnUm6m2d9JJPfkQudnD8sBQKG9A',
                    'pid' => $newIdOfUserFolder,
                    'tx_styleguide_containsdemo' => 'tx_styleguide_frontend',
                ],
            ],
        ];

        if ($useSiteSets) {
            // Set backend layout on root page
            $data['pages'][$newIdOfEntryPage]['backend_layout'] = 'pagets__StyleguideFrontend';
            $data['pages'][$newIdOfEntryPage]['backend_layout_next_level'] = 'pagets__StyleguideFrontend';
            $newIdOfTemplateFolder = StringUtility::getUniqueId('NEW');
            $newIdOfDummyTemplateSubsite = StringUtility::getUniqueId('NEW');
            $newIdOfExtTsTemplate = StringUtility::getUniqueId('NEW');
            // Storage for demo sys_template records
            $data['pages'][$newIdOfTemplateFolder] = [
                'title' => 'template records',
                'pid' => $newIdOfEntryPage,
                'tx_styleguide_containsdemo' => 'tx_styleguide_frontend',
                'hidden' => 0,
                'doktype' => 254,
            ];
            $data['pages'][$newIdOfDummyTemplateSubsite] = [
                'title' => 'template record subsite',
                'pid' => $newIdOfTemplateFolder,
                'tx_styleguide_containsdemo' => 'tx_styleguide_frontend',
                'hidden' => 0,
                'doktype' => 254,
            ];
            $data['sys_template'] = [
                $newIdOfExtTsTemplate => [
                    'title' => 'styleguide frontend demo extension template',
                    'root' => 0,
                    'clear' => 0,
                    'include_static_file' => '',
                    'constants' => '',
                    'config' => '',
                    'pid' => $newIdOfTemplateFolder,
                ],
            ];
        } else {
            $newIdOfRootTsTemplate = StringUtility::getUniqueId('NEW');
            $data['sys_template'] = [
                $newIdOfRootTsTemplate => [
                    'title' => 'root styleguide frontend demo',
                    'root' => 1,
                    'clear' => 3,
                    'include_static_file' => 'EXT:styleguide/Configuration/TypoScript',
                    'constants' => '',
                    'config' => '',
                    'pid' => $newIdOfEntryPage,
                ],
            ];
        }

        $neighborPage = $newIdOfEntryPage;
        $contentData = $this->getElementContent();

        foreach ($contentData as $type => $ce) {
            $newIdOfPage = StringUtility::getUniqueId('NEW');
            $data['pages'][$newIdOfPage] = [
                'title' => $type,
                'tx_styleguide_containsdemo' => 'tx_styleguide_frontend',
                'hidden' => 0,
                'abstract' => $this->kauderwelschService->getLoremIpsum(),
                'pid' => $neighborPage,
                'categories' => $newIdOfCategory,
            ];

            // Set keyword for menu_related_pages to show up
            if (substr($type, 0, 5) === 'menu_') {
                $data['pages'][$newIdOfPage]['keywords'] = 'Bacon';
            }

            foreach ($ce as $content) {
                $newIdOfContent = StringUtility::getUniqueId('NEW');
                $data['tt_content'][$newIdOfContent] = $content;
                $data['tt_content'][$newIdOfContent]['CType'] = $type;
                $data['tt_content'][$newIdOfContent]['pid'] = $newIdOfPage;
                $data['tt_content'][$newIdOfContent]['categories'] = $newIdOfCategory;

                if ($type === 'menu_categorized_content') {
                    $data['tt_content'][$newIdOfContent]['selected_categories'] = $newIdOfCategory;
                    $data['tt_content'][$newIdOfContent]['category_field'] = 'categories';
                }

                if ($type === 'menu_categorized_pages') {
                    $data['tt_content'][$newIdOfContent]['selected_categories'] = $newIdOfCategory;
                }

                $data['tt_content'][$newIdOfContent]['tx_styleguide_containsdemo'] = 'tx_styleguide_frontend';
            }
        }

        $this->executeDataHandler($data);

        // Create site configuration for frontend
        if (isset($GLOBALS['TYPO3_REQUEST']) && empty($basePath)) {
            $port = $GLOBALS['TYPO3_REQUEST']->getUri()->getPort() ? ':' . $GLOBALS['TYPO3_REQUEST']->getUri()->getPort() : '';
            $domain = $GLOBALS['TYPO3_REQUEST']->getUri()->getScheme() . '://' . $GLOBALS['TYPO3_REQUEST']->getUri()->getHost() . $port . '/';
        } else {
            // On cli there is no TYPO3_REQUEST object
            $domain = empty($basePath) ? '/' : $basePath;
        }
        $topPageUid = (int)$this->recordFinder->findUidsOfFrontendPages(['tx_styleguide_frontend_root'])[0];
        $this->createSiteConfiguration($topPageUid, $domain, 'Styleguide frontend demo', $useSiteSets ? ['typo3/styleguide'] : []);

        $this->populateSysFileReference();
        $this->populateTtContentPages();
        $this->populateTtContentRecords();
        $this->populateFeUserAndGroup();
    }

    public function delete(): void
    {
        $commands = [];

        // Delete frontend pages - also deletes tt_content, sys_category and sys_file_references
        $frontendPagesUids = $this->recordFinder->findUidsOfFrontendPages();
        if (!empty($frontendPagesUids)) {
            foreach ($frontendPagesUids as $page) {
                $commands['pages'][(int)$page]['delete'] = 1;
            }
        }

        // Delete site configuration
        try {
            $rootUid = $this->recordFinder->findUidsOfFrontendPages(['tx_styleguide_frontend_root']);

            if (!empty($rootUid)) {
                $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByRootPageId((int)$rootUid[0]);
                $identifier = $site->getIdentifier();
                GeneralUtility::makeInstance(SiteWriter::class)->delete($identifier);
            }
        } catch (SiteNotFoundException $e) {
            // Do not throw a thing if site config does not exist
        }
        // Delete records data
        $this->executeDataHandler([], $commands);

        // Delete created files
        $this->deleteFalFolder('styleguide_frontend');
    }

    /**
     * Return array of all content elements to create
     *
     * @return array
     */
    protected function getElementContent(): array
    {
        return [
            'bullets' => [
                [
                    'header' => 'A bullet list',
                    'bodytext' => "Item 1\nItem 2\nItem 3\n",
                ],
                [
                    'header' => 'Another bullet list',
                    'bodytext' => "Item 4\nItem 5\nItem 6\n",
                ],
            ],
            'div' => [
                [
                    'header' => $this->kauderwelschService->getLoremIpsum(),
                ],
                [
                    'header' => $this->kauderwelschService->getLoremIpsum(),
                ],
            ],
            'header' => [
                [
                    'header' => $this->kauderwelschService->getLoremIpsum(),
                ],
                [
                    'header' => $this->kauderwelschService->getLoremIpsum(),
                    'header_layout' => 2,
                ],
                [
                    'header' => $this->kauderwelschService->getLoremIpsum(),
                    'header_layout' => 3,
                ],
            ],
            'text' => [
                [
                    'header' => $this->kauderwelschService->getLoremIpsum(),
                    'subheader' => $this->kauderwelschService->getLoremIpsum(),
                    'bodytext' => $this->kauderwelschService->getLoremIpsumHtml() . ' ' . $this->kauderwelschService->getLoremIpsumHtml(),
                ],
                [
                    'header' => $this->kauderwelschService->getLoremIpsum(),
                    'header_layout' => 3,
                    'bodytext' => $this->kauderwelschService->getLoremIpsumHtml() . ' ' . $this->kauderwelschService->getLoremIpsumHtml(),
                ],
            ],
            'textpic' => [ // @todo add images
                [
                    'header' => $this->kauderwelschService->getLoremIpsum(),
                    'header_layout' => 5,
                    'subheader' => $this->kauderwelschService->getLoremIpsum(),
                    'bodytext' => $this->kauderwelschService->getLoremIpsumHtml() . ' ' . $this->kauderwelschService->getLoremIpsumHtml(),
                ],
                [
                    'header' => $this->kauderwelschService->getLoremIpsum(),
                    'header_layout' => 2,
                    'bodytext' => $this->kauderwelschService->getLoremIpsumHtml() . ' ' . $this->kauderwelschService->getLoremIpsumHtml(),
                ],
            ],
            'textmedia' => [
                [
                    'header' => $this->kauderwelschService->getLoremIpsum(),
                    'header_layout' => 5,
                    'subheader' => $this->kauderwelschService->getLoremIpsum(),
                    'bodytext' => $this->kauderwelschService->getLoremIpsumHtml() . ' ' . $this->kauderwelschService->getLoremIpsumHtml(),
                ],
                [
                    'header' => $this->kauderwelschService->getLoremIpsum(),
                    'header_layout' => 2,
                    'bodytext' => $this->kauderwelschService->getLoremIpsumHtml() . ' ' . $this->kauderwelschService->getLoremIpsumHtml(),
                    'imageorient' => 25,
                ],
            ],
            'image' => [
                [
                    'header' => $this->kauderwelschService->getLoremIpsum(),
                    'bodytext' => $this->kauderwelschService->getLoremIpsumHtml() . ' ' . $this->kauderwelschService->getLoremIpsumHtml(),
                ],
            ],
            'html' => [
                [
                    'header' => $this->kauderwelschService->getLoremIpsum(),
                    'bodytext' => $this->kauderwelschService->getLoremIpsumHtml() . ' ' . $this->kauderwelschService->getLoremIpsumHtml(),
                ],
            ],
            'table' => [
                [
                    'header' => $this->kauderwelschService->getLoremIpsum(),
                    'bodytext' => "row1 col1|row1 col2|row1 col3|row1 col4\nrow2 col1|row2 col2|row2 col3|row2 col4",
                ],
                [
                    'header' => $this->kauderwelschService->getLoremIpsum(),
                    'bodytext' => "row1 col1|row1 col2|row1 col3|row1 col4\nrow2 col1|row2 col2|row2 col3|row2 col4\nrow3 col1|row3 col2|row3 col3|row3 col4\nrow4 col1|row4 col2|row4 col3|row4 col4",
                ],
            ],
            'felogin_login' => [
                [
                    'header' => $this->kauderwelschService->getLoremIpsum(),
                ],
            ],
            'form_formframework' => [
                [
                    'header' => 'Advanced form - all fields',
                    'pi_flexform' => [
                        'data' => [
                            'sDEF' => [
                                'lDEF' => [
                                    'settings.persistenceIdentifier' => [
                                        'vDEF' => 'EXT:styleguide/Resources/Private/Forms/allfields.form.yaml',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                [
                    'header' => 'Simple form',
                    'pi_flexform' => [
                        'data' => [
                            'sDEF' => [
                                'lDEF' => [
                                    'settings.persistenceIdentifier' => [
                                        'vDEF' => 'EXT:styleguide/Resources/Private/Forms/simpleform.form.yaml',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'indexedsearch_pi2' => [
                [
                    'header' => 'Indexed Search',
                ],
            ],
            'shortcut' => [
                [
                    'header' => 'Shortcut',
                    'records' => '', // UIDs 856,857,849
                ],
            ],
            'uploads' => [
                [
                    'header' => 'Uploads',
                ],
            ],
            'menu_categorized_pages' => [
                [
                    'header' => 'Menu categorized pages',
                    'records' => '', // UIDs 856,857,849
                ],
            ],
            'menu_categorized_content' => [
                [
                    'header' => 'Menu categorized content',
                    'records' => '', // UIDs 856,857,849
                ],
            ],
            'menu_pages' => [
                [
                    'header' => 'Menu pages',
                    'records' => '', // UIDs 856,857,849
                ],
            ],
            'menu_subpages' => [
                [
                    'header' => 'Menu subpages',
                    'records' => '', // UIDs 856,857,849
                ],
            ],
            'menu_sitemap' => [
                [
                    'header' => 'Menu sitemap',
                    'records' => '', // UIDs 856,857,849
                ],
            ],
            'menu_section' => [
                [
                    'header' => 'Menu section',
                    'records' => '', // UIDs 856,857,849
                ],
            ],
            'menu_abstract' => [
                [
                    'header' => 'Menu abstract',
                    'records' => '', // UIDs 856,857,849
                ],
            ],
            'menu_recently_updated' => [
                [
                    'header' => 'Menu recently updated',
                    'records' => '', // UIDs 856,857,849
                ],
            ],
            'menu_related_pages' => [
                [
                    'header' => 'Menu related pages',
                    'records' => '', // UIDs 856,857,849
                ],
            ],
            'menu_section_pages' => [
                [
                    'header' => 'Menu section pages',
                    'records' => '', // UIDs 856,857,849
                ],
            ],
            'menu_sitemap_pages' => [
                [
                    'header' => 'Menu sitemap pages',
                    'records' => '', // UIDs 856,857,849
                ],
            ],
        ];
    }

    /**
     * Append file reference to existing content elements
     */
    protected function populateSysFileReference(): void
    {
        $files = $this->recordFinder->findDemoFileObjects('styleguide_frontend');

        $recordData = [];
        foreach ($this->recordFinder->findTtContent() as $content) {
            switch ($content['CType']) {
                case 'textmedia':
                    $fieldname = 'assets';
                    break;
                case 'uploads':
                    $fieldname = 'media';
                    break;
                default:
                    $fieldname = 'image';
            }

            foreach ($files as $image) {
                $newId = StringUtility::getUniqueId('NEW');
                $recordData['sys_file_reference'][$newId] = [
                    'table_local' => 'sys_file',
                    'uid_local' => $image->getUid(),
                    'uid_foreign' => $content['uid'],
                    'tablenames' => 'tt_content',
                    'fieldname' => $fieldname,
                    'pid' => $content['pid'],
                ];
            }
        }

        $this->executeDataHandler($recordData);
    }

    /**
     * Append PIDs to tt_content field for menu_* ctype
     */
    protected function populateTtContentPages(string $field = 'pages', int $count = 5): void
    {
        $pages = $this->recordFinder->findUidsOfFrontendPages();
        $contentElements = $this->recordFinder->findTtContent(['menu_pages', 'menu_subpages', 'menu_section', 'menu_abstract', 'menu_recently_updated', 'menu_section_pages', 'menu_sitemap_pages']);

        $recordData = [];
        foreach ($contentElements as $content) {
            $recordData['tt_content'][$content['uid']] = [
                $field => implode(',', array_slice($pages, 0, $count)),
            ];
        }

        $this->executeDataHandler($recordData);
    }

    /**
     * Append content UID for ctype shortcut
     *
     * @param string $field
     */
    protected function populateTtContentRecords(string $field = 'records'): void
    {
        $shortcutToElement = $this->recordFinder->findTtContent(['text'])[0]['uid'];
        $contentElements = $this->recordFinder->findTtContent(['shortcut']);

        $recordData = [];
        foreach ($contentElements as $content) {
            $recordData['tt_content'][$content['uid']] = [
                $field => $shortcutToElement,
            ];
        }

        $this->executeDataHandler($recordData);
    }

    private function populateFeUserAndGroup(): void
    {
        $ceFeLogin = $this->recordFinder->findTtContent(['felogin_login']);
        $storageFeLogin = $this->recordFinder->findUidsOfFrontendPages(['tx_styleguide_frontend_root', 'tx_styleguide_frontend'], [254])[0];
        $feUsers = $this->recordFinder->findFeUsers();
        $feGroups = $this->recordFinder->findFeUserGroups();
        $feGroupUids = implode(',', array_column($feGroups, 'uid'));

        $recordData = [];
        foreach ($feUsers as $login) {
            $recordData['fe_users'][$login['uid']] = [
                'usergroup' => $feGroupUids,
            ];
        }

        // Set storage pid for content element to 'frontend user'
        foreach ($ceFeLogin as $ce) {
            $recordData['tt_content'][$ce['uid']] = [
                'pi_flexform' => [
                    'data' => [
                        'sDEF' => [
                            'lDEF' => [
                                'settings.pages' => [
                                    'vDEF' => $storageFeLogin,
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        $this->executeDataHandler($recordData);
    }
}
