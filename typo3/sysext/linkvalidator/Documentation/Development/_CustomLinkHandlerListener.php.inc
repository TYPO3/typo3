<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\DataHandling\Event\AppendLinkHandlerElementsEvent;

#[AsEventListener]
final class AppendShopLinkElements
{
    public function __invoke(AppendLinkHandlerElementsEvent $event): void
    {
        $linkParts = $event->getLinkParts();
        // Link details of "t3://shop?..." as built by the link handler
        if (($linkParts['type'] ?? '') !== 'shop') {
            return;
        }

        $tokenId = $event->getTokenId();
        $event->addElements([
            $tokenId . ':' . $event->getIdx() => [
                'matchString' => $event->getContent(),
                'subst' => [
                    'type' => 'shop',
                    'tokenID' => $tokenId,
                    'tokenValue' => (string)($linkParts['identifier'] ?? ''),
                ],
            ],
        ]);
    }
}
