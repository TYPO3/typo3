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

namespace TYPO3\CMS\Frontend\ContentObject;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Frontend\ContentObject\Exception\ContentRenderingException;

/**
 * Registry to create cObjects (e.g. TEXT)
 * @internal
 */
class ContentObjectFactory
{
    public function __construct(private ContainerInterface $contentObjectLocator) {}

    public function getContentObject(string $name, ServerRequestInterface $request, ContentObjectRenderer $contentObjectRenderer): ?AbstractContentObject
    {
        if (!$this->contentObjectLocator->has($name)) {
            return null;
        }

        $contentObject = $this->contentObjectLocator->get($name);
        if (!($contentObject instanceof AbstractContentObject)) {
            throw new ContentRenderingException(sprintf('Registered content object class name "%s" must be an instance of AbstractContentObject, but is not!', get_class($contentObject)), 1422564295);
        }

        $contentObject->setRequest($request);
        $contentObject->setContentObjectRenderer($contentObjectRenderer);

        return $contentObject;
    }
}
