<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Linktype;

use TYPO3\CMS\Linkvalidator\LinkAnalyzer;
use TYPO3\CMS\Linkvalidator\Linktype\AbstractLinktype;

final class ShopLinktype extends AbstractLinktype
{
    protected string $identifier = 'shop';

    public function checkLink(string $url, array $softRefEntry, LinkAnalyzer $reference): bool
    {
        // $url is the "tokenValue" set in the event listener
        return $this->productExists($url);
    }

    private function productExists(string $identifier): bool
    {
        // Ask your shop API or database
        return true;
    }
}
