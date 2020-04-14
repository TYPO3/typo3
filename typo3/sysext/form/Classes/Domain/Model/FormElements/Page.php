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

/*
 * Inspired by and partially taken from the Neos.Form package (www.neos.io)
 */

namespace TYPO3\CMS\Form\Domain\Model\FormElements;

use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Model\Renderable\CompositeRenderableInterface;
use TYPO3\CMS\Form\Exception as FormException;

/**
 * A Page, being part of a bigger FormDefinition. It contains numerous FormElements
 * as children.
 *
 * A FormDefinition consists of multiple Pages, where only one page is visible
 * at any given time.
 *
 * Most of the API of this object is implemented in {@link AbstractSection},
 * so make sure to review this class as well.
 *
 * Please see {@link FormDefinition} for an in-depth explanation.
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 */
class Page extends AbstractSection
{

    /**
     * Constructor. Needs this Page's identifier
     *
     * @param string $identifier The Page's identifier
     * @param string $type The Page's type
     * @throws \TYPO3\CMS\Form\Domain\Exception\IdentifierNotValidException if the identifier was no non-empty string
     */
    public function __construct(string $identifier, string $type = 'Page')
    {
        parent::__construct($identifier, $type);
    }

    /**
     * Set the parent renderable
     *
     * @param CompositeRenderableInterface $parentRenderable
     * @throws FormException
     */
    public function setParentRenderable(CompositeRenderableInterface $parentRenderable)
    {
        if (!($parentRenderable instanceof FormDefinition)) {
            throw new FormException(sprintf('The specified parentRenderable must be a FormDefinition, got "%s"', is_object($parentRenderable) ? get_class($parentRenderable) : gettype($parentRenderable)), 1329233747);
        }
        parent::setParentRenderable($parentRenderable);
    }
}
