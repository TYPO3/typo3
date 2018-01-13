<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Model\Renderable;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It originated from the Neos.Form package (www.neos.io)
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
 * Interface which all Form Parts must adhere to **when they have sub elements**.
 * This includes especially "FormDefinition" and "Page".
 *
 * Scope: frontend
 * **This class is NOT meant to be sub classed by developers.**
 */
interface CompositeRenderableInterface extends RenderableInterface
{

    /**
     * Returns all RenderableInterface instances of this composite renderable recursively
     *
     * @return \TYPO3\CMS\Form\Domain\Model\Renderable\RenderableInterface[]
     * @internal
     */
    public function getRenderablesRecursively(): array;
}
