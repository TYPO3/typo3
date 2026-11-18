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

namespace TYPO3\CMS\Core\Security\Authorization\Policy;

/**
 * Names a resource by a type and an identifier within that type, e.g. the
 * page type "254" or the custom option "tx_news:publish".
 *
 * It is the subject of isGranted() for resources that have no object of
 * their own: isGranted(Scope::WRITE, ResourceIdentifier::pageType(254)).
 * Resources with an object are checked on that object instead, e.g. a
 * table on its TcaSchema or a module on its ModuleInterface. A language is
 * checked on its SiteLanguage, or on its id where no site is at hand.
 *
 * The constants name the types TYPO3 Core grants through backend groups.
 * The ResourceVoter decides on those without an object of their own, the
 * LanguageVoter on languages; the types table, field, module and widget
 * are only used by the voters of TYPO3 Core towards the policy decision
 * point. An extension may use a type
 * of its own, prefixed with its extension key, and decides on it with a
 * voter of its own: isGranted(Scope::ACCESS, new ResourceIdentifier('tx_shop_discount', 'staff')).
 * The ResourceVoter abstains on such types.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication modernization groundwork
 */
final readonly class ResourceIdentifier
{
    public const string TABLE = 'table';
    public const string ADMIN_ONLY_TABLE = 'admin_only_table';
    public const string FIELD = 'field';
    public const string FIELD_VALUE = 'field_value';
    public const string LANGUAGE = 'language';
    public const string PAGE_TYPE = 'page_type';
    public const string MODULE = 'module';
    public const string WIDGET = 'widget';
    public const string MFA_PROVIDER = 'mfa_provider';
    public const string CUSTOM_OPTION = 'custom_option';

    /**
     * @param non-empty-string $type
     */
    public function __construct(
        public string $type,
        public string $identifier,
    ) {}

    /**
     * @internal for the voters of TYPO3 Core, check a TcaSchema instead
     */
    public static function table(string $table): self
    {
        return new self(self::TABLE, $table);
    }

    /**
     * @internal for the voters of TYPO3 Core, check a TcaSchema instead
     */
    public static function adminOnlyTable(string $table): self
    {
        return new self(self::ADMIN_ONLY_TABLE, $table);
    }

    /**
     * @internal for the voters of TYPO3 Core, check a SchemaField instead
     */
    public static function field(string $table, string $field): self
    {
        return new self(self::FIELD, $table . ':' . $field);
    }

    public static function fieldValue(string $table, string $field, string $value): self
    {
        return new self(self::FIELD_VALUE, $table . ':' . $field . ':' . $value);
    }

    /**
     * A language without a site, see the LanguageVoter
     */
    public static function language(int $languageId): self
    {
        return new self(self::LANGUAGE, (string)$languageId);
    }

    public static function pageType(int $doktype): self
    {
        return new self(self::PAGE_TYPE, (string)$doktype);
    }

    /**
     * @internal for the voters of TYPO3 Core, check a ModuleInterface instead
     */
    public static function module(string $identifier): self
    {
        return new self(self::MODULE, $identifier);
    }

    /**
     * @internal for the voters of TYPO3 Core, check a WidgetConfigurationInterface instead
     */
    public static function widget(string $identifier): self
    {
        return new self(self::WIDGET, $identifier);
    }

    public static function mfaProvider(string $identifier): self
    {
        return new self(self::MFA_PROVIDER, $identifier);
    }

    /**
     * @param string $identifier the option as "group:item"
     */
    public static function customOption(string $identifier): self
    {
        return new self(self::CUSTOM_OPTION, $identifier);
    }
}
