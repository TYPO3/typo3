<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\LinkHandling;

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
 * Resolves links to records and the parameters given
 */
class RecordLinkHandler implements LinkHandlingInterface
{
    /**
     * The Base URN for this link handling to act on
     *
     * @var string
     */
    protected $baseUrn = 't3://record';

    /**
     * Returns all valid parameters for linking to a TYPO3 page as a string
     *
     * @param array $parameters
     * @return string
     * @throws \InvalidArgumentException
     */
    public function asString(array $parameters): string
    {
        if (empty($parameters['identifier']) || empty($parameters['uid'])) {
            throw new \InvalidArgumentException('The RecordLinkHandler expects identifier and uid as $parameter configuration.', 1486155150);
        }
        $urn = $this->baseUrn;
        $urn .= sprintf('?identifier=%s&uid=%s', $parameters['identifier'], $parameters['uid']);

        return $urn;
    }

    /**
     * Returns all relevant information built in the link to a page (see asString())
     *
     * @param array $data
     * @return array
     * @throws \InvalidArgumentException
     */
    public function resolveHandlerData(array $data): array
    {
        if (empty($data['identifier']) || empty($data['uid'])) {
            throw new \InvalidArgumentException('The RecordLinkHandler expects identifier, uid as $data configuration', 1486155151);
        }

        return $data;
    }
}
