<?php
namespace TYPO3\CMS\Rtehtmlarea\Controller;

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

/**
 * Render custom attribute data-htmlarea-clickenlarge
 */
class CustomAttributeController extends \TYPO3\CMS\Frontend\Plugin\AbstractPlugin
{
    // Default plugin variables:
    /**
     * @var string
     */
    public $prefixId = 'tx_rtehtmlarea_pi3';

    // Same as class name
    /**
     * @var string
     */
    public $scriptRelPath = 'pi3/class.tx_rtehtmlarea_pi3.php';

    // Path to this script relative to the extension dir.
    /**
     * @var string
     */
    public $extKey = 'rtehtmlarea';

    // The extension key.
    /**
     * @var array
     */
    public $conf = [];

    /**
     * cObj object
     *
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    public $cObj;

    /**
     * Rendering the "data-htmlarea-clickenlarge" custom attribute, called from TypoScript
     *
     * @param string $content Content input. Not used, ignore.
     * @param array $conf TypoScript configuration
     * @return string HTML output.
     * @access private
     */
    public function render_clickenlarge($content, $conf)
    {
        $clickenlarge = isset($this->cObj->parameters['data-htmlarea-clickenlarge']) ? $this->cObj->parameters['data-htmlarea-clickenlarge'] : 0;
        if (!$clickenlarge) {
            // Backward compatibility
            $clickenlarge = isset($this->cObj->parameters['clickenlarge']) ? $this->cObj->parameters['clickenlarge'] : 0;
        }
        $fileFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
        $fileUid = $this->cObj->parameters['data-htmlarea-file-uid'];
        if ($fileUid) {
            $fileObject = $fileFactory->getFileObject($fileUid);
            $filePath = $fileObject->getForLocalProcessing(false);
            $file = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($filePath);
        } else {
            // Pre-FAL backward compatibility
            $path = $this->cObj->parameters['src'];
            $magicFolder = $fileFactory->getFolderObjectFromCombinedIdentifier($GLOBALS['TYPO3_CONF_VARS']['BE']['RTE_imageStorageDir']);
            if ($magicFolder instanceof \TYPO3\CMS\Core\Resource\Folder) {
                $magicFolderPath = $magicFolder->getPublicUrl();
                $pathPre = $magicFolderPath . 'RTEmagicC_';
                if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($path, $pathPre)) {
                    // Find original file:
                    $pI = pathinfo(substr($path, strlen($pathPre)));
                    $filename = substr($pI['basename'], 0, -strlen(('.' . $pI['extension'])));
                    $file = $magicFolderPath . 'RTEmagicP_' . $filename;
                } else {
                    $file = $this->cObj->parameters['src'];
                }
            }
        }
        // Unset clickenlarge custom attribute
        unset($this->cObj->parameters['data-htmlarea-clickenlarge']);
        // Backward compatibility
        unset($this->cObj->parameters['clickenlarge']);
        unset($this->cObj->parameters['allParams']);
        $content = '<img ' . \TYPO3\CMS\Core\Utility\GeneralUtility::implodeAttributes($this->cObj->parameters, true, true) . ' />';
        if ($clickenlarge && is_array($conf['imageLinkWrap.'])) {
            $theImage = $file ? $this->frontendController->tmpl->getFileName($file) : '';
            if ($theImage) {
                $this->cObj->parameters['origFile'] = $theImage;
                if ($this->cObj->parameters['title']) {
                    $conf['imageLinkWrap.']['title'] = $this->cObj->parameters['title'];
                }
                if ($this->cObj->parameters['alt']) {
                    $conf['imageLinkWrap.']['alt'] = $this->cObj->parameters['alt'];
                }
                $content = $this->cObj->imageLinkWrap($content, $theImage, $conf['imageLinkWrap.']);
                $content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
            }
        }
        return $content;
    }
}
