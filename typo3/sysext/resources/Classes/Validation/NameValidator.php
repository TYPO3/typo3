<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Validation;

use InvalidArgumentException;
use Webmozart\Assert\Assert;

/**
 * Inspired by https://github.com/kubernetes/apimachinery/blob/master/pkg/util/validation/validation.go
 * Described here https://kubernetes.io/docs/concepts/overview/working-with-objects/names/
 */
final class NameValidator
{

    private const QUALIFIED_NAME_CHARACTER_FORMAT = "[A-Za-z0-9]";
    private const QUALIFIED_NAME_EXTRA_CHARACTER_FORMAT = "[-A-Za-z0-9_.]";
    private const QUALIFIED_NAME_FORMAT = "(" . self::QUALIFIED_NAME_CHARACTER_FORMAT . self::QUALIFIED_NAME_EXTRA_CHARACTER_FORMAT . "*)?" . self::QUALIFIED_NAME_CHARACTER_FORMAT;
    private const QUALIFIED_NAME_ERROR_MESSAGE = "must consist of alphanumeric characters, '-', '_' or '.', and must start and end with an alphanumeric character";
    private const QUALIFIED_NAME_MAX_LENGTH = 63;

    /**
     * Tests whether the value passed is what TYPO3 calls a "qualified name". This is a format used in various places throughout the system.
     */
    public static function isQualifiedName(string $value): void
    {
        Assert::stringNotEmpty($value);
        $parts = explode('/', $value);
        switch (count($parts)) {
            case 1:
                $name = $parts[0];
                break;
            case 2:
                [$prefix, $name] = $parts;
                Assert::stringNotEmpty($prefix);
                self::isDNS1123Subdomain($prefix);
                break;
            default:
                Assert::countBetween($parts, 1, 2);
        }
        Assert::stringNotEmpty($name);
        Assert::maxLength($name, self::QUALIFIED_NAME_MAX_LENGTH);
        self::isStringMatchingFormat($name, self::QUALIFIED_NAME_FORMAT, self::QUALIFIED_NAME_ERROR_MESSAGE);
    }

    /**
     * Allowed characters in an HTTP Path as defined by RFC 3986. A HTTP path may contain:
     * * unreserved characters (alphanumeric, '-', '.', '_', '~')
     * * percent-encoded octets
     * * sub-delims ("!", "$", "&", "'", "(", ")", "*", "+", ",", ";", "=")
     * * a colon character (":")
     */
    private const HTTP_PATH_FORMAT = '[A-Za-z0-9/\-._~%!\$&\'()*+,;=:]+';

    /**
     * Checks if the given string is a domain-prefixed path (e.g. acme.io/foo). All characters
     * before the first "/" must be a valid subdomain as defined by RFC 1123. All characters trailing the first "/" must
     * be valid HTTP Path characters as defined by RFC 3986.
     */
    public static function isDomainPrefixedPath(string $value): void
    {
        Assert::notEmpty($value);
        Assert::contains($value, '/');
        [$host, $path] = explode('/', $value, 2);
        Assert::allNotEmpty([$host, $path], 'must be a domain-prefixed path (such as \"acme.io/foo\")');
        self::isDNS1123Subdomain($host);
        self::isStringMatchingFormat($path, self::HTTP_PATH_FORMAT);
    }

    private const LABEL_VALUE_FORMAT = "(" . self::QUALIFIED_NAME_FORMAT . ")?";
    private const LABEL_VALUE_ERROR_MESSAGE = "A valid label must be an empty string or consist of alphanumeric characters, '-', '_' or '.', and must start and end with an alphanumeric character";
    private const LABEL_VALUE_MAX_LENGTH = 63;

    /**
     * Tests whether the value passed is a valid label value.
     * @throws InvalidArgumentException
     */
    public static function isValidLabelValue(string $value): void
    {
        Assert::maxLength($value, self::LABEL_VALUE_MAX_LENGTH);
        self::isStringMatchingFormat($value, self::LABEL_VALUE_FORMAT, self::LABEL_VALUE_ERROR_MESSAGE);
    }

    private const DNS1123_LABEL_FORMAT = "[a-z0-9]([-a-z0-9]*[a-z0-9])?";
    private const DNS1123_LABEL_ERROR_MESSAGE = "A lowercase RFC 1123 label must consist of lower case alphanumeric characters or '-', and must start and end with an alphanumeric character";
    private const DNS1123_LABEL_MAX_LENGTH = 63;

    /**
     * Test for a string that conforms to the definition of a label in DNS (RFC 1123).
     * @throws InvalidArgumentException
     */
    public static function isDNS1123Label(string $value): void
    {
        Assert::maxLength($value, self::DNS1123_LABEL_MAX_LENGTH);
        self::isStringMatchingFormat($value, self::DNS1123_LABEL_FORMAT, self::DNS1123_LABEL_ERROR_MESSAGE);
    }


    private const DNS1123_SUBDOMAIN_FORMAT = self::DNS1123_LABEL_FORMAT . "(\\." . self::DNS1123_LABEL_FORMAT . ")*";
    private const DNS1123_SUBDOMAIN_ERROR_MESSAGE = "A lowercase RFC 1123 subdomain must consist of lower case alphanumeric characters, '-' or '.', and must start and end with an alphanumeric character";
    private const DNS1123_SUBDOMAIN_MAX_LENGTH = 253;

    /**
     * Tests for a string that conforms to the definition of a subdomain in DNS (RFC 1123).
     * @throws InvalidArgumentException
     */
    public static function isDNS1123Subdomain(string $value): void
    {
        Assert::maxLength($value, self::DNS1123_SUBDOMAIN_MAX_LENGTH);
        self::isStringMatchingFormat($value, self::DNS1123_SUBDOMAIN_FORMAT, self::DNS1123_SUBDOMAIN_ERROR_MESSAGE);
    }

    /**
     * Tests for a string that conforms to a given format (partial regex).
     * @throws InvalidArgumentException
     */
    private static function isStringMatchingFormat(string $value, string $format, string $message = ''): void
    {
        Assert::regex($value, '#^' . $format . '$#', $message);
    }
}
