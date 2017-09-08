<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;

/**
 * view helper
 * @internal
 */
class DownloadExtensionViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Form\AbstractFormViewHelper
{
    /**
     * @var string
     */
    protected $tagName = 'form';

    /**
     * @var \TYPO3\CMS\Extbase\Service\ExtensionService
     */
    protected $extensionService;

    /**
     * @param \TYPO3\CMS\Extbase\Service\ExtensionService $extensionService
     */
    public function injectExtensionService(\TYPO3\CMS\Extbase\Service\ExtensionService $extensionService)
    {
        $this->extensionService = $extensionService;
    }

    /**
     * Initialize arguments.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('extension', Extension::class, '', true);
        $this->registerTagAttribute('enctype', 'string', 'MIME type with which the form is submitted');
        $this->registerTagAttribute('method', 'string', 'Transfer type (GET or POST)');
        $this->registerTagAttribute('name', 'string', 'Name of form');
        $this->registerTagAttribute('onreset', 'string', 'JavaScript: On reset of the form');
        $this->registerTagAttribute('onsubmit', 'string', 'JavaScript: On submit of the form');
        $this->registerUniversalTagAttributes();
    }

    /**
     * Renders a download link
     *
     * @return string the rendered a tag
     */
    public function render()
    {
        /** @var Extension $extension */
        $extension = $this->arguments['extension'];
        $installPaths = Extension::returnAllowedInstallPaths();
        if (empty($installPaths)) {
            return '';
        }
        $pathSelector = '<ul class="is-hidden">';
        foreach ($installPaths as $installPathType => $installPath) {
            $pathSelector .= '<li>
				<input type="radio" id="' . htmlspecialchars($extension->getExtensionKey()) . '-downloadPath-' . htmlspecialchars($installPathType) . '" name="' . htmlspecialchars($this->getFieldNamePrefix()) . '[downloadPath]" class="downloadPath" value="' . htmlspecialchars($installPathType) . '" ' . ($installPathType === 'Local' ? 'checked="checked"' : '') . ' />
				<label for="' . htmlspecialchars($extension->getExtensionKey()) . '-downloadPath-' . htmlspecialchars($installPathType) . '">' . htmlspecialchars($installPathType) . '</label>
			</li>';
        }
        $pathSelector .= '</ul>';
        $uriBuilder = $this->renderingContext->getControllerContext()->getUriBuilder();
        $action = 'checkDependencies';
        $uriBuilder->reset();
        $uriBuilder->setFormat('json');
        $uri = $uriBuilder->uriFor($action, [
            'extension' => (int)$extension->getUid()
        ], 'Download');
        $this->tag->addAttribute('data-href', $uri);

        $automaticInstallation = (bool)GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('extensionmanager', 'automaticInstallation');
        $labelKeySuffix = $automaticInstallation ? '' : '.downloadOnly';
        $label = '
			<div class="btn-group">
				<button
					title="' . LocalizationUtility::translate('extensionList.downloadViewHelper.submit' . $labelKeySuffix, 'extensionmanager') . '"
					type="submit"
					class="btn btn-default"
					value="' . LocalizationUtility::translate('extensionList.downloadViewHelper.submit' . $labelKeySuffix, 'extensionmanager') . '"
				>
					<span class="t3-icon fa fa-cloud-download"></span>
				</button>
			</div>';

        $this->tag->setContent($label . $pathSelector);
        $this->tag->addAttribute('class', 'download');
        return '<div id="' . htmlspecialchars($extension->getExtensionKey()) . '-downloadFromTer" class="downloadFromTer">' . $this->tag->render() . '</div>';
    }

    /**
     * Get the field name prefix
     *
     * @return string
     */
    protected function getFieldNamePrefix()
    {
        if ($this->hasArgument('fieldNamePrefix')) {
            return $this->arguments['fieldNamePrefix'];
        }
        return $this->getDefaultFieldNamePrefix();
    }

    /**
     * Retrieves the default field name prefix for this form
     *
     * @return string default field name prefix
     */
    protected function getDefaultFieldNamePrefix()
    {
        $request = $this->renderingContext->getControllerContext()->getRequest();
        if ($this->hasArgument('extensionName')) {
            $extensionName = $this->arguments['extensionName'];
        } else {
            $extensionName = $request->getControllerExtensionName();
        }
        if ($this->hasArgument('pluginName')) {
            $pluginName = $this->arguments['pluginName'];
        } else {
            $pluginName = $request->getPluginName();
        }
        if ($extensionName !== null && $pluginName != null) {
            return $this->extensionService->getPluginNamespace($extensionName, $pluginName);
        }
        return '';
    }
}
