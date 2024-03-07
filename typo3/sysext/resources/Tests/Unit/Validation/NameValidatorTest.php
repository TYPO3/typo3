<?php declare(strict_types=1);

namespace TYPO3\CMS\Resources\Tests\Unit\Validation;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Resources\Validation\NameValidator;

class NameValidatorTest extends TestCase
{

    #[Test]
    #[DataProvider('goodDNS1123Labels')]
    public function itValidatesGoodDNS1123Labels(string $value)
    {
        NameValidator::IsDNS1123Label($value);
        $this->assertTrue(true);
    }

    public static function goodDNS1123Labels(): array
    {
        return array_map(function($value): array { return (array) $value; }, [
            "a", "ab", "abc", "a1", "a-1", "a--1--2--b",
            "0", "01", "012", "1a", "1-a", "1--a--b--2",
            str_repeat("a", 63)
        ]);
    }

    #[Test]
    #[DataProvider('badDNS1123Labels')]
    public function itRejectsBadDNS1123Labels(string $value)
    {
        $this->expectException(\InvalidArgumentException::class);
        NameValidator::isDNS1123Label($value);
    }

    public static function badDNS1123Labels(): array
    {
        return array_map(function($value): array { return (array) $value; }, [
            "", "A", "ABC", "aBc", "A1", "A-1", "1-A",
            "-", "a-", "-a", "1-", "-1",
            "_", "a_", "_a", "a_b", "1_", "_1", "1_2",
            ".", "a.", ".a", "a.b", "1.", ".1", "1.2",
            " ", "a ", " a", "a b", "1 ", " 1", "1 2",
            str_repeat("a", 64)
        ]);
    }

    #[Test]
    #[DataProvider('goodDNS1123Subdomains')]
    public function itValidatesGoodDNS1123Subdomain(string $value)
    {
        NameValidator::isDNS1123Subdomain($value);
        $this->assertTrue(true);
    }

    public static function goodDNS1123Subdomains(): array
    {
        return array_map(function($value): array { return (array) $value; }, [
            "a", "ab", "abc", "a1", "a-1", "a--1--2--b",
            "0", "01", "012", "1a", "1-a", "1--a--b--2",
            "a.a", "ab.a", "abc.a", "a1.a", "a-1.a", "a--1--2--b.a",
            "a.1", "ab.1", "abc.1", "a1.1", "a-1.1", "a--1--2--b.1",
            "0.a", "01.a", "012.a", "1a.a", "1-a.a", "1--a--b--2",
            "0.1", "01.1", "012.1", "1a.1", "1-a.1", "1--a--b--2.1",
            "a.b.c.d.e", "aa.bb.cc.dd.ee", "1.2.3.4.5", "11.22.33.44.55",
            str_repeat("a", 253),
        ]);
    }

    #[Test]
    #[DataProvider('badDNS1123Subdomains')]
    public function itRejectsBadDNS1123Subdomain(string $value)
    {
        $this->expectException(\InvalidArgumentException::class);
        NameValidator::isDNS1123Subdomain($value);
    }

    public static function badDNS1123Subdomains(): array
    {
        return array_map(function($value): array { return (array) $value; }, [
            "", "A", "ABC", "aBc", "A1", "A-1", "1-A",
            "-", "a-", "-a", "1-", "-1",
            "_", "a_", "_a", "a_b", "1_", "_1", "1_2",
            ".", "a.", ".a", "a..b", "1.", ".1", "1..2",
            " ", "a ", " a", "a b", "1 ", " 1", "1 2",
            "A.a", "aB.a", "ab.A", "A1.a", "a1.A",
            "A.1", "aB.1", "A1.1", "1A.1",
            "0.A", "01.A", "012.A", "1A.a", "1a.A",
            "A.B.C.D.E", "AA.BB.CC.DD.EE", "a.B.c.d.e", "aa.bB.cc.dd.ee",
            "a@b", "a,b", "a_b", "a;b",
            "a:b", "a%b", "a?b", "a\$b",
            str_repeat("a", 254),
        ]);
    }

    #[Test]
    #[DataProvider('goodQualifiedNames')]
    public function itValidatesGoodQualifiedNames(string $value)
    {
        NameValidator::isQualifiedName($value);
        $this->assertTrue(true);
    }

    public static function goodQualifiedNames(): array
    {
        return array_map(function($value): array { return (array) $value; }, [
            "simple",
            "now-with-dashes",
            "1-starts-with-num",
            "1234",
            "simple/simple",
            "now-with-dashes/simple",
            "now-with-dashes/now-with-dashes",
            "now.with.dots/simple",
            "now-with.dashes-and.dots/simple",
            "1-num.2-num/3-num",
            "1234/5678",
            "1.2.3.4/5678",
            "Uppercase_Is_OK_123",
            "example.com/Uppercase_Is_OK_123",
            "requests.storage-foo",
            str_repeat("a", 63),
            str_repeat("a", 253) . "/" . str_repeat("b", 63),
        ]);
    }

    #[Test]
    #[DataProvider('badQualifiedNames')]
    public function itRejectsBadQualifiedNames(string $value)
    {
        $this->expectException(\InvalidArgumentException::class);
        NameValidator::isQualifiedName($value);
    }

    public static function badQualifiedNames(): array
    {
        return array_map(function($value): array { return (array) $value; }, [
            "nospecialchars%^=@",
            "cantendwithadash-",
            "-cantstartwithadash-",
            "only/one/slash",
            "Example.com/abc",
            "example_com/abc",
            "example.com/",
            "/simple",
            str_repeat("a", 64),
            str_repeat("a", 254) . "/abc",
        ]);
    }

    #[Test]
    #[DataProvider('goodDomainPrefixedPaths')]
    public function itValidatesGoodDomainPrefixedPaths(string $value)
    {
        NameValidator::isDomainPrefixedPath($value);
        $this->assertTrue(true);
    }

    public static function goodDomainPrefixedPaths(): array
    {
        return array_map(function($value): array { return (array) $value; }, [
            "a/b",
            "a/b/c/d",
            "a.com/foo",
            "a.b.c.d/foo",
            "typo3.org/foo/bar",
            "typo3.org/FOO/BAR",
            "dev.typo3.org/more/path",
            "this.is.a.really.long.fqdn/even/longer/path/just/because",
            "bbc.co.uk/path/goes/here",
            "10.0.0.1/foo",
            "hyphens-are-good.typo3.org/and-in-paths-too",
            str_repeat("a", 240) . ".typo3.org/a",
            "typo3.org/" . str_repeat("a", 240),
        ]);
    }

    #[Test]
    #[DataProvider('badDomainPrefixedPaths')]
    public function itRejectsBadDomainPrefixedPaths(string $value)
    {
        $this->expectException(\InvalidArgumentException::class);
        NameValidator::isDomainPrefixedPath($value);
    }

    public static function badDomainPrefixedPaths(): array
    {
        return array_map(function($value): array { return (array) $value; }, [
            ".",
            "...",
            "/b",
            "com",
            ".com",
            "a.b.c.d/foo?a=b",
            "a.b.c.d/foo#a",
            "Dev.typo3.org",
            ".foo.example.com",
            "*.example.com",
            "example.com/foo{}[]@^`",
            "underscores_are_bad.typo3.org",
            "underscores_are_bad.typo3.org/foo",
            "foo@bar.example.com",
            "foo@bar.example.com/foo",
            str_repeat("a", 247) . ".typo3.org",
        ]);
    }

    #[Test]
    #[DataProvider('goodLabelValues')]
    public function itValidatesGoodLabelValues(string $value)
    {
        NameValidator::isValidLabelValue($value);
        $this->assertTrue(true);
    }

    public static function goodLabelValues(): array
    {
        return array_map(function($value): array { return (array) $value; }, [
            "simple",
            "now-with-dashes",
            "1-starts-with-num",
            "end-with-num-1",
            "1234",
            str_repeat("a", 63),
            "",
        ]);
    }

    #[Test]
    #[DataProvider('badLabelValues')]
    public function itRejectsBadLabelValues(string $value)
    {
        $this->expectException(\InvalidArgumentException::class);
        NameValidator::isValidLabelValue($value);
    }

    public static function badLabelValues(): array
    {
        return array_map(function($value): array { return (array) $value; }, [
            "nospecialchars%^=@",
            "Tama-nui-te-rā.is.Māori.sun",
            "\\backslashes\\are\\bad",
            "-starts-with-dash",
            "ends-with-dash-",
            ".starts.with.dot",
            "ends.with.dot.",
            str_repeat("a", 64),
        ]);
    }

}
