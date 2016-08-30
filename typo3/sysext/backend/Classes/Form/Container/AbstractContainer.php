<?php
namespace TYPO3\CMS\Backend\Form\Container;

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

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Abstract container has various methods used by the container classes
 */
abstract class AbstractContainer extends AbstractNode
{
    /**
     * Instance of the node factory to create sub elements and container.
     *
     * @var NodeFactory
     */
    protected $nodeFactory;

    /**
     * Container objects give $nodeFactory down to other containers.
     *
     * @param NodeFactory $nodeFactory
     * @param array $data
     */
    public function __construct(NodeFactory $nodeFactory, array $data)
    {
        parent::__construct($nodeFactory, $data);
        $this->nodeFactory = $nodeFactory;
    }

    /**
     * A single field of TCA 'types' 'showitem' can have four semicolon separated configuration options:
     *   fieldName: Name of the field to be found in TCA 'columns' section
     *   fieldLabel: An alternative field label
     *   paletteName: Name of a palette to be found in TCA 'palettes' section that is rendered after this field
     *   extra: Special configuration options of this field
     *
     * @param string $field Semicolon separated field configuration
     * @throws \RuntimeException
     * @return array
     */
    protected function explodeSingleFieldShowItemConfiguration($field)
    {
        $fieldArray = GeneralUtility::trimExplode(';', $field);
        if (empty($fieldArray[0])) {
            throw new \RuntimeException('Field must not be empty', 1426448465);
        }
        return [
            'fieldName' => $fieldArray[0],
            'fieldLabel' => $fieldArray[1] ?: null,
            'paletteName' => $fieldArray[2] ?: null,
        ];
    }

    /**
     * Render tabs with label and content. Used by TabsContainer and FlexFormTabsContainer.
     * Re-uses the template Tabs.html which is also used by ModuleTemplate.php.
     *
     * @param array $menuItems Tab elements, each element is an array with "label" and "content"
     * @param string $domId DOM id attribute, will be appended with an iteration number per tab.
     * @return string
     */
    protected function renderTabMenu(array $menuItems, $domId, $defaultTabIndex = 1)
    {
        $templatePathAndFileName = 'EXT:backend/Resources/Private/Templates/DocumentTemplate/Tabs.html';
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templatePathAndFileName));
        $view->assignMultiple([
            'id' => $domId,
            'items' => $menuItems,
            'defaultTabIndex' => $defaultTabIndex,
            'wrapContent' => false,
            'storeLastActiveTab' => true,
        ]);
        return $view->render();
    }

    /**
     * Rendering preview output of a field value which is not shown as a form field but just outputted.
     *
     * @param string $value The value to output
     * @param array $config Configuration for field.
     * @param string $field Name of field.
     * @return string HTML formatted output
     */
    protected function previewFieldValue($value, $config, $field = '')
    {
        if ($config['config']['type'] === 'group' && ($config['config']['internal_type'] === 'file' || $config['config']['internal_type'] === 'file_reference')) {
            // Ignore upload folder if internal_type is file_reference
            if ($config['config']['internal_type'] === 'file_reference') {
                $config['config']['uploadfolder'] = '';
            }
            $table = 'tt_content';
            // Making the array of file items:
            $itemArray = GeneralUtility::trimExplode(',', $value, true);
            // Showing thumbnails:
            $thumbnail = '';
            $imgs = [];
            $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
            foreach ($itemArray as $imgRead) {
                $imgParts = explode('|', $imgRead);
                $imgPath = rawurldecode($imgParts[0]);
                $rowCopy = [];
                $rowCopy[$field] = $imgPath;
                // Icon + click menu:
                $absFilePath = GeneralUtility::getFileAbsFileName($config['config']['uploadfolder'] ? $config['config']['uploadfolder'] . '/' . $imgPath : $imgPath);
                $fileInformation = pathinfo($imgPath);
                $title = $fileInformation['basename'] . ($absFilePath && @is_file($absFilePath))
                    ? ' (' . GeneralUtility::formatSize(filesize($absFilePath)) . ')'
                    : ' - FILE NOT FOUND!';
                $fileIcon = '<span title="' . htmlspecialchars($title) . '">' . $iconFactory->getIconForFileExtension($fileInformation['extension'], Icon::SIZE_SMALL)->render() . '</span>';
                $imgs[] =
                    '<span class="text-nowrap">' .
                    BackendUtility::thumbCode(
                        $rowCopy,
                        $table,
                        $field,
                        '',
                        'thumbs.php',
                        $config['config']['uploadfolder'], 0, ' align="middle"'
                    ) .
                    ($absFilePath ? BackendUtility::wrapClickMenuOnIcon($fileIcon, $absFilePath, 0, 1, '', '+copy,info,edit,view') : $fileIcon) .
                    $imgPath .
                    '</span>';
            }
            return implode('<br />', $imgs);
        } else {
            return nl2br(htmlspecialchars($value));
        }
    }
}
