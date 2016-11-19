<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Domain\Renderer;

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

use TYPO3\CMS\Form\Domain\Exception\TypeDefinitionNotFoundException;
use TYPO3\CMS\Form\Domain\Model\Renderable\RootRenderableInterface;

/**
 * Renderer for unknown Form Elements
 * This is used to render Form Elements without definition depending on the context:
 * In "preview mode" (e.g. inside the FormEditor) a div with an error message is rendered
 * If previewMode is FALSE this will return an empty string if the rendering Option "skipUnknownElements" is TRUE for
 * the form, or throw an Exception otherwise.
 *
 * Scope: frontend
 */
class UnknownFormElementRenderer extends AbstractElementRenderer
{

    /**
     * This renders the given $renderable depending on the context:
     * In preview Mode this returns an error message. Otherwise this throws an exception or returns an empty string
     * depending on the "skipUnknownElements" rendering option
     *
     * @param RootRenderableInterface $renderable
     * @return string the rendered $renderable
     * @throws TypeDefinitionNotFoundException
     * @internal
     */
    public function render(RootRenderableInterface $renderable): string
    {
        $renderingOptions = $this->formRuntime->getRenderingOptions();
        $previewMode = isset($renderingOptions['previewMode']) && $renderingOptions['previewMode'] === true;
        if ($previewMode) {
            return sprintf('<div class="t3-form-unknown-element" data-element-identifier-path="%s"><em>Unknown Form Element "%s"</em></div>', htmlspecialchars($this->getRenderablePath($renderable)), htmlspecialchars($renderable->getType()));
        }
        $skipUnknownElements = isset($renderingOptions['skipUnknownElements']) && $renderingOptions['skipUnknownElements'] === true;
        if (!$skipUnknownElements) {
            throw new TypeDefinitionNotFoundException(sprintf('Type "%s" not found. Probably some configuration is missing.', $renderable->getType()), 1382364019);
        }
        return '';
    }

    /**
     * Returns the path of a $renderable in the format <formIdentifier>/<sectionIdentifier>/<sectionIdentifier>/.../<elementIdentifier>
     *
     * @param RootRenderableInterface $renderable
     * @return string
     * @internal
     */
    protected function getRenderablePath(RootRenderableInterface $renderable): string
    {
        $path = $renderable->getIdentifier();
        while ($renderable = $renderable->getParentRenderable()) {
            $path = $renderable->getIdentifier() . '/' . $path;
        }
        return $path;
    }
}
