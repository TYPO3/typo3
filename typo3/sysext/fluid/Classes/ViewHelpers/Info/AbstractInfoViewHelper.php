<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Info;

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
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Base class for information retrieval ViewHelpers
 */
abstract class AbstractInfoViewHelper extends AbstractViewHelper
{
    /**
     * Initialize/register arguments
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('as', 'string', 'Optional template variable to assign - if not used, ViewHelper returns the info array directly');
    }

    /**
     * @return string|array
     */
    public function render()
    {
        return static::renderStatic($this->arguments, $this->buildRenderChildrenClosure(), $this->renderingContext);
    }

    /**
     * @return array
     */
    protected static function getData()
    {
        return [];
    }

    /**
     * @return TypoScriptFrontendController
     */
    protected static function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string|array
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext)
    {
        $data = static::getTypoScriptFrontendController()->cObj->data;
        if (empty($arguments['as'])) {
            return $data;
        } else {
            $provider = $renderingContext->getVariableProvider();
            $provider->add($arguments['as'], $data);
            $content = $renderChildrenClosure();
            $provider->remove($arguments['as']);
            return $content;
        }
    }
}
