<?php

namespace MyVendor\NyExtension\Linktype\ExternalLinktype;

use TYPO3\CMS\Linkvalidator\LinkAnalyzer;
use TYPO3\CMS\Linkvalidator\Linktype\ExternalLinktype as LinkvalidatorExternalLinkType;

// This class inherits from ExternalLinktype,
// so it is only necessary to override some methods.
class ExternalLinktype extends LinkvalidatorExternalLinkType
{
    // This class must use a different identifier because "external" is already used.
    protected string $identifier = 'custom_external';

    public function checkLink(
        string $origUrl,
        array $softRefEntry,
        LinkAnalyzer $reference
    ): bool {
        // do additional stuff here or after parent::checkLink
        // ...
        return parent::checkLink($origUrl, $softRefEntry, $reference);
    }

    public function fetchType(array $value, string $type, string $key): string
    {
        preg_match_all(
            '/((?:http|https))(?::\\/\\/)(?:[^\\s<>]+)/i',
            (string)$value['tokenValue'],
            $urls,
            PREG_PATTERN_ORDER
        );
        if (!empty($urls[0][0])) {
            $type = $this->getIdentifier();
        }
        return $type;
    }
}
