<?php
namespace TYPO3\CMS\Rtehtmlarea\ImageHandler;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController;
use TYPO3\CMS\Recordlist\LinkHandler\LinkHandlerInterface;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Rtehtmlarea\Controller\SelectImageController;

class EditImageHandler implements LinkHandlerInterface, LinkParameterProviderInterface
{
    /**
     * @var SelectImageController
     */
    protected $selectImageController;

    /**
     * @var FileInterface
     */
    protected $currentFile;

    /**
     * Initialize the handler
     *
     * @param AbstractLinkBrowserController $linkBrowser
     * @param string $identifier
     * @param array $configuration Page TSconfig
     *
     * @return void
     */
    public function initialize(AbstractLinkBrowserController $linkBrowser, $identifier, array $configuration)
    {
        if (!$linkBrowser instanceof SelectImageController) {
            throw new \InvalidArgumentException('The given $linkBrowser must be of type SelectImageController."', 1455499721);
        }
        $this->selectImageController = $linkBrowser;
    }

    /**
     * Checks if this is the handler for the given link
     *
     * The handler may store this information locally for later usage.
     *
     * @param array $linkParts Link parts as returned from TypoLinkCodecService
     *
     * @return bool
     */
    public function canHandleLink(array $linkParts)
    {
        if (!empty($linkParts['currentImage'])) {
            try {
                $this->currentFile = ResourceFactory::getInstance()->getFileObject($linkParts['currentImage']);
                return true;
            } catch (FileDoesNotExistException $e) {
            }
        }
        return false;
    }

    /**
     * Format the current link for HTML output
     *
     * @return string
     */
    public function formatCurrentUrl()
    {
        return $this->currentFile->getStorage()->getName() . ': ' . $this->currentFile->getIdentifier();
    }

    /**
     * Disallow this handler if no image is there to edit
     *
     * @param array $allowedItems
     * @return array
     */
    public function modifyAllowedItems($allowedItems, $linkParts)
    {
        $selfPosition = array_search('image', $allowedItems, true);
        if (empty($linkParts['currentImage']) && $selfPosition !== false) {
            unset($allowedItems[$selfPosition]);
        }
        return $allowedItems;
    }

    /**
     * Render the link handler
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function render(ServerRequestInterface $request)
    {
        GeneralUtility::makeInstance(PageRenderer::class)->loadRequireJsModule('TYPO3/CMS/Rtehtmlarea/EditImage');

        $buttonConfig = $this->selectImageController->getButtonConfiguration();
        $removedProperties = [];
        if (is_array($buttonConfig['properties.'])) {
            if ($buttonConfig['properties.']['removeItems']) {
                $removedProperties = GeneralUtility::trimExplode(',', $buttonConfig['properties.']['removeItems'], true);
            }
        }

        $rteProperties = $this->selectImageController->getRteProperties();

        $lockPlainWidth = false;
        $lockPlainHeight = false;
        if (isset($rteProperties['default.']['proc.']['plainImageMode'])) {
            $plainImageMode = $rteProperties['default.']['proc.']['plainImageMode'];
            $lockPlainWidth = $plainImageMode === 'lockDimensions';
            $lockPlainHeight = $lockPlainWidth || $plainImageMode === 'lockRatio' || $plainImageMode === 'lockRatioWhenSmaller';
        }

        $lang = $this->getLanguageService();

        $content = '<div><form name="imageData" class="t3js-editForm"><table class="htmlarea-window-table">';

        if (!in_array('class', $removedProperties, true) && !empty($buttonConfig['properties.']['class.']['allowedClasses'])) {
            $classesImageArray = GeneralUtility::trimExplode(',', $buttonConfig['properties.']['class.']['allowedClasses'], true);
            $classesImageJSOptions = '<option value=""></option>';
            foreach ($classesImageArray as $class) {
                $classesImageJSOptions .= '<option value="' . $class . '">' . $class . '</option>';
            }
            $content .= '<tr><td><label for="iClass">' . $lang->getLL('class') . ': </label></td><td>
                <select id="t3js-iClass" name="iClass" style="width:140px;">' . $classesImageJSOptions . '
                </select></td></tr>';
        }
        if (!in_array('width', $removedProperties, true) && !($this->currentFile && $lockPlainWidth /* && check if it is a RTE magic image (no clue how to do that now with FAL)*/)) {
            $content .= '<tr><td><label for="iWidth">' . $lang->getLL('width') . ': </label></td><td>
                <input type="text" id="t3js-iWidth" name="iWidth" value="" style="width: 39px;" maxlength="4" /></td></tr>';
        }
        if (!in_array('height', $removedProperties, true) && !($this->currentFile && $lockPlainHeight /* && check if it is a RTE magic image (no clue how to do that now with FAL)*/)) {
            $content .= '<tr><td><label for="iHeight">' . $lang->getLL('height') . ': </label></td><td>
                <input type="text" id="t3js-iHeight" name="iHeight" value="" style="width: 39px;" maxlength="4" /></td></tr>';
        }
        if (!in_array('border', $removedProperties, true)) {
            $content .= '<tr><td><label for="iBorder">' . $lang->getLL('border') . ': </label></td><td>
                <input type="checkbox" id="t3js-iBorder" name="iBorder" value="1" /></td></tr>';
        }
        if (!in_array('float', $removedProperties, true)) {
            $content .= '<tr><td><label for="iFloat">' . $lang->getLL('float') . ': </label></td><td>
                <select id="t3js-iFloat" name="iFloat">'
                        . '<option value="">' . $lang->getLL('notSet') . '</option>'
                        . '<option value="none">' . $lang->getLL('nonFloating') . '</option>'
                        . '<option value="left">' . $lang->getLL('left') . '</option>'
                        . '<option value="right">' . $lang->getLL('right') . '</option>'
                        . '</select>
                </td></tr>';
        }
        if (!in_array('paddingTop', $removedProperties, true)) {
            $content .= '<tr><td><label for="iPaddingTop">' . $lang->getLL('padding_top') . ': </label></td><td><input type="text" id="t3js-iPaddingTop" name="iPaddingTop" value="" style="width: 39px;" maxlength="4" /></td></tr>';
        }
        if (!in_array('paddingRight', $removedProperties, true)) {
            $content .= '<tr><td><label for="iPaddingRight">' . $lang->getLL('padding_right') . ': </label></td><td><input type="text" id="t3js-iPaddingRight" name="iPaddingRight" value="" style="width: 39px;" maxlength="4" /></td></tr>';
        }
        if (!in_array('paddingBottom', $removedProperties, true)) {
            $content .= '<tr><td><label for="iPaddingBottom">' . $lang->getLL('padding_bottom') . ': </label></td><td><input type="text" id="t3js-iPaddingBottom" name="iPaddingBottom" value="" style="width: 39px;" maxlength="4" /></td></tr>';
        }
        if (!in_array('paddingLeft', $removedProperties, true)) {
            $content .= '<tr><td><label for="iPaddingLeft">' . $lang->getLL('padding_left') . ': </label></td><td><input type="text" id="t3js-iPaddingLeft" name="iPaddingLeft" value="" style="width: 39px;" maxlength="4" /></td></tr>';
        }
        if (!in_array('title', $removedProperties, true)) {
            $content .= '<tr><td><label for="iTitle">' . $lang->getLL('title') . ': </label></td><td><input type="text" id="t3js-iTitle" name="iTitle" style="width:192px;" maxlength="256" /></td></tr>';
        }
        if (!in_array('alt', $removedProperties, true)) {
            $content .= '<tr><td><label for="iAlt">' . $lang->getLL('alt') . ': </label></td><td><input type="text" id="t3js-iAlt" name="iAlt" style="width:192px;" maxlength="256" /></td></tr>';
        }
        if (!in_array('lang', $removedProperties, true)) {
            $content .= '<tr id="t3js-languageSetting"><td><label for="iLang"></label></td><td><select id="t3js-iLang" name="iLang">###lang_selector###</select></td></tr>';
        }
        if (!in_array('data-htmlarea-clickenlarge', $removedProperties, true) && !in_array('clickenlarge', $removedProperties, true)) {
            $content .= '<tr><td><label for="iClickEnlarge">' . $lang->sL('LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:image_zoom') . ': </label></td><td><input type="checkbox" name="iClickEnlarge" id="t3js-iClickEnlarge" value="0" /></td></tr>';
        }
        $content .= '<tr><td></td><td><input class="btn btn-default" type="submit" value="' . $lang->getLL('update') . '"></td></tr></table></form>';

        return $content;
    }

    /**
     * Return TRUE if the handler supports to update a link.
     *
     * This is useful for file or page links, when only attributes are changed.
     *
     * @return bool
     */
    public function isUpdateSupported()
    {
        return false;
    }

    /**
     * @return string[] Array of body-tag attributes
     */
    public function getBodyTagAttributes()
    {
        return [
            'data-classes-image' => $this->selectImageController->getButtonConfiguration()['properties.']['class.']['allowedClasses'] || $this->selectImageController->getRteProperties()['default.']['classesImage']
        ];
    }

    /**
     * Returns the URL of the current script
     *
     * @return string
     */
    public function getScriptUrl()
    {
        return $this->selectImageController->getScriptUrl();
    }

    /**
     * Provides an array or GET parameters for URL generation
     *
     * @param array $values Array of values to include into the parameters or which might influence the parameters
     *
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values)
    {
        return [];
    }

    /**
     * Check if given value is currently the selected item
     *
     * This method is only used in the page tree.
     *
     * @param array $values Values to be checked
     *
     * @return bool Returns TRUE if the given values match the currently selected item
     */
    public function isCurrentlySelectedItem(array $values)
    {
        return false;
    }

    /**
     * @return array
     */
    public function getLinkAttributes()
    {
        return [];
    }

    /**
     * @param string[] $fieldDefinitions Array of link attribute field definitions
     * @return string[]
     */
    public function modifyLinkAttributes(array $fieldDefinitions)
    {
        return $fieldDefinitions;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
