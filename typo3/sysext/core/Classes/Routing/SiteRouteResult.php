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

namespace TYPO3\CMS\Core\Routing;

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Site\Entity\SiteInterface;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Class, usually available within request attribute "routing"
 * containing all the findings of the Routers.
 * When doing page-based routing the SiteRouteResult will get replaced with the PageArguments object.
 */
class SiteRouteResult implements RouteResultInterface
{
    /**
     * @var array
     */
    protected $validProperties = ['uri', 'site', 'language', 'tail'];

    /**
     * Incoming URI which was processed.
     * @var UriInterface
     */
    protected $uri;

    /**
     * @var SiteInterface
     */
    protected $site;

    /**
     * @var SiteLanguage|null
     */
    protected $language;

    /**
     * data bag with additional attributes
     * @var array
     */
    protected $data;

    /**
     * The leftover string of the path from the uri
     * @var string
     */
    protected $tail;

    public function __construct(UriInterface $uri, SiteInterface $site, SiteLanguage $language = null, string $tail = '', array $data = [])
    {
        $this->uri = $uri;
        $this->site = $site;
        $this->language = $language;
        $this->tail = $tail;
        $this->data = $data;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function getSite(): SiteInterface
    {
        return $this->site;
    }

    public function getLanguage(): ?SiteLanguage
    {
        return $this->language;
    }

    public function getTail(): string
    {
        return $this->tail;
    }

    public function offsetExists($offset): bool
    {
        return in_array($offset, $this->validProperties, true) || isset($this->data[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed|UriInterface|string|SiteInterface|SiteLanguage
     * @todo Set parameter type for $offset to mixed as breaking change in v12.
     * @todo Set return type to mixed as breaking change in v12 and remove #[\ReturnTypeWillChange].
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        switch ($offset) {
            case 'uri':
                return $this->uri;
            case 'site':
                return $this->site;
            case 'language':
                return $this->language;
            case 'tail':
                return $this->tail;
            default:
                return $this->data[$offset];
        }
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @todo Set parameter type for $offset and $value to mixed as breaking change in v12.
     * @todo Set return type to mixed as breaking change in v12 and remove #[\ReturnTypeWillChange].
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        switch ($offset) {
            case 'uri':
                throw new \InvalidArgumentException('You can never replace the URI in a route result', 1535462423);
            case 'site':
                throw new \InvalidArgumentException('You can never replace the Site object in a route result', 1535462454);
            case 'language':
                throw new \InvalidArgumentException('You can never replace the Language object in a route result', 1535462452);
            case 'tail':
                $this->tail = $value;
                break;
            default:
                $this->data[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @todo Set parameter type for $offset to mixed as breaking change in v12.
     * @todo Set return type to void as breaking change in v12 and remove #[\ReturnTypeWillChange].
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        switch ($offset) {
            case 'uri':
                throw new \InvalidArgumentException('You can never replace the URI in a route result', 1535462429);
            case 'site':
                throw new \InvalidArgumentException('You can never replace the Site object in a route result', 1535462458);
            case 'language':
                $this->language = null;
                break;
            case 'tail':
                $this->tail = '';
                break;
            default:
                unset($this->data[$offset]);
        }
    }
}
