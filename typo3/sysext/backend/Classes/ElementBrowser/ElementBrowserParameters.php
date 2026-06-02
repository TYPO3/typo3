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

namespace TYPO3\CMS\Backend\ElementBrowser;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Data Transfer Object for Element Browser parameters.
 *
 * Provides type-safe access to the parameters passed between FormEngine and
 * the Element Browser.
 *
 * @internal This class is not part of the TYPO3 Core API.
 */
final readonly class ElementBrowserParameters implements \JsonSerializable
{
    /**
     * @param string $fieldReference Form field name reference, e.g., "data[tt_content][123][image]"
     * @param string $rteParameters Legacy RTE parameters (editorNo:contentTypo3Language) - deprecated, kept for BC @deprecated Remove in v15.0
     * @param string $rteConfiguration Legacy RTE configuration (RTEtsConfigParams) - deprecated, kept for BC @deprecated Remove in v15.0
     * @param string $allowedTypes Allowed types: tables (comma-separated) for db mode, or file extensions for file mode
     * @param string $disallowedFileExtensions Disallowed file extensions (comma-separated) for file mode
     * @param string $irreObjectId IRRE uniqueness target, e.g., "data-4-pages-4-nav_icon-sys_file_reference"
     */
    public function __construct(
        public string $fieldReference = '',
        public string $rteParameters = '',
        public string $rteConfiguration = '',
        public string $allowedTypes = '',
        public string $disallowedFileExtensions = '',
        public string $irreObjectId = '',
        public bool $useEvents = false,
    ) {}

    /**
     * Creates an instance from the current HTTP request.
     */
    public static function fromRequest(ServerRequestInterface $request): self
    {
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody() ?? [];

        return new self(
            fieldReference: (string)($parsedBody['fieldReference'] ?? $queryParams['fieldReference'] ?? ''),
            rteParameters: (string)($parsedBody['rteParameters'] ?? $queryParams['rteParameters'] ?? ''),
            rteConfiguration: (string)($parsedBody['rteConfiguration'] ?? $queryParams['rteConfiguration'] ?? ''),
            allowedTypes: (string)($parsedBody['allowedTypes'] ?? $queryParams['allowedTypes'] ?? ''),
            disallowedFileExtensions: (string)($parsedBody['disallowedFileExtensions'] ?? $queryParams['disallowedFileExtensions'] ?? ''),
            irreObjectId: (string)($parsedBody['irreObjectId'] ?? $queryParams['irreObjectId'] ?? ''),
            useEvents: (bool)(int)($parsedBody['useEvents'] ?? $queryParams['useEvents'] ?? 0),
        );
    }

    /**
     * Returns the allowed file extensions as an array.
     *
     * @return string[] List of allowed file extensions
     */
    public function getAllowedFileExtensions(): array
    {
        if ($this->allowedTypes === '' || $this->allowedTypes === '*') {
            return [];
        }

        // Skip if it looks like a table name (contains underscore typical for TYPO3 tables)
        if (str_contains($this->allowedTypes, 'sys_file')) {
            return [];
        }

        return GeneralUtility::trimExplode(',', $this->allowedTypes, true);
    }

    /**
     * Returns the disallowed file extensions as an array.
     *
     * @return string[] List of disallowed file extensions
     */
    public function getDisallowedFileExtensions(): array
    {
        if ($this->disallowedFileExtensions === '') {
            return [];
        }

        return GeneralUtility::trimExplode(',', $this->disallowedFileExtensions, true);
    }

    /**
     * Parses the allowed file extensions from the allowedTypes field.
     *
     * @return array{allowed: string[], disallowed: string[]}
     */
    public function getFileExtensions(): array
    {
        return [
            'allowed' => $this->getAllowedFileExtensions(),
            'disallowed' => $this->getDisallowedFileExtensions(),
        ];
    }

    /**
     * Parses the allowed tables from the allowedTypes field.
     *
     * @return string[] List of allowed table names
     */
    public function getAllowedTables(): array
    {
        if ($this->allowedTypes === '' || $this->allowedTypes === '*') {
            return [];
        }

        return GeneralUtility::trimExplode(',', $this->allowedTypes, true);
    }

    /**
     * Returns the field reference parsed into table name and field name.
     *
     * Parses format like "data[tt_content][123][image]" to extract
     * table name ("tt_content") and field name ("image").
     *
     * @return array{tableName: string, fieldName: string}
     */
    public function getFieldReferenceParts(): array
    {
        $result = [
            'tableName' => '',
            'fieldName' => '',
        ];

        if ($this->fieldReference === '') {
            return $result;
        }

        // Parse "data[table][uid][field]" format
        $parts = explode('[', $this->fieldReference);
        if (count($parts) >= 4) {
            // parts[1] = "table]", parts[3] = "field]"
            $result['tableName'] = rtrim($parts[1], ']');
            $result['fieldName'] = rtrim($parts[3], ']');
        }

        return $result;
    }

    /**
     * Returns data attributes for use in HTML elements (body tag).
     *
     * @return array<string, string|null>
     */
    public function toDataAttributes(): array
    {
        return [
            // @deprecated Remove in v15.0: data-form-field-name is a legacy attribute
            'data-form-field-name' => 'data[' . $this->fieldReference . '][' . $this->rteParameters . '][' . $this->rteConfiguration . ']',
            'data-field-reference' => $this->fieldReference,
            // @deprecated Remove in v15.0: data-rte-parameters is a legacy attribute
            'data-rte-parameters' => $this->rteParameters ?: null,
            // @deprecated Remove in v15.0: data-rte-configuration is a legacy attribute
            'data-rte-configuration' => $this->rteConfiguration ?: null,
            'data-irre-object-id' => $this->irreObjectId ?: null,
            'data-use-events' => $this->useEvents ? 'true' : null,
        ];
    }

    /**
     * Returns array representation of the parameters.
     *
     * @return array{
     *   fieldReference: string,
     *   rteParameters: string,
     *   rteConfiguration: string,
     *   allowedTypes: string,
     *   disallowedFileExtensions: string,
     *   irreObjectId: string,
     *   useEvents: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'fieldReference' => $this->fieldReference,
            'rteParameters' => $this->rteParameters,
            'rteConfiguration' => $this->rteConfiguration,
            'allowedTypes' => $this->allowedTypes,
            'disallowedFileExtensions' => $this->disallowedFileExtensions,
            'irreObjectId' => $this->irreObjectId,
            'useEvents' => $this->useEvents,
        ];
    }

    /**
     * Returns URL query parameters array (new format).
     *
     * @return array<string, string>
     */
    public function toQueryParameters(): array
    {
        $params = [];
        if ($this->fieldReference !== '') {
            $params['fieldReference'] = $this->fieldReference;
        }
        if ($this->rteParameters !== '') {
            $params['rteParameters'] = $this->rteParameters;
        }
        if ($this->rteConfiguration !== '') {
            $params['rteConfiguration'] = $this->rteConfiguration;
        }
        if ($this->allowedTypes !== '') {
            $params['allowedTypes'] = $this->allowedTypes;
        }
        if ($this->disallowedFileExtensions !== '') {
            $params['disallowedFileExtensions'] = $this->disallowedFileExtensions;
        }
        if ($this->irreObjectId !== '') {
            $params['irreObjectId'] = $this->irreObjectId;
        }
        if ($this->useEvents) {
            $params['useEvents'] = '1';
        }
        return $params;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
