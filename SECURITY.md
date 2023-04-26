# Security Policy

## Supported Versions

The following matrix shows the TYPO3 versions currently maintained by the TYPO3
community. Sprint releases (versions before 12.4.0 and 11.5.0, in their
corresponding branches) have reached their end of support and don't receive any
further bug fixes or security patches.

| Version  | Supported          |
|----------|--------------------|
| 12.4.x   | :white_check_mark: |
| 12.3.x   | :x:                |
| 12.2.x   | :x:                |
| 12.1.x   | :x:                |
| 12.0.x   | :x:                |
| 11.5.x   | :white_check_mark: |
| < 11.5.0 | :x:                |

## Reporting a Vulnerability

Please report vulnerabilities to [security@typo3.org](mailto:security@typo3.org).
Your report should include the following details:

* The affected project (either the TYPO3 Core or a TYPO3 extension).
* The exact version or version range that you analysed.
* A step-by-step explanation of how to exploit the potential vulnerability.

You can use the following GPG/PGP key ID to optionally encrypt your messages to
[security@typo3.org](mailto:security@typo3.org):

* Key ID: `C05FBE60`
* Fingerprint: `B41C C3EF 373E 0F5C 7018  7FE9 3BEF BD27 C05F BE60`

You can download the public key from the following sources:

* [typo3.org](https://typo3.org/fileadmin/t3o_common_storage/keys/B41CC3EF373E0F5C70187FE93BEFBD27C05FBE60.asc)
* [keys.openpgp.org](https://keys.openpgp.org/vks/v1/by-fingerprint/B41CC3EF373E0F5C70187FE93BEFBD27C05FBE60)

## Coordinated Disclosure

> :warning: We urge security researchers not to publish vulnerabilities in issue trackers or
discuss them publicly (e.g. on Slack or Twitter).

The [TYPO3 Security Team](https://typo3.org/community/teams/security) coordinates
the process with the TYPO3 core developers, extension maintainers and other
affected parties. Once a security fix is available, we prepare a new release and
publish the fixed version. At the same time, we communicate the vulnerability and
the fix to the public by using various communication channels such as:

* [TYPO3 Security Advisories](https://typo3.org/help/security-advisories)
* [TYPO3 Security Team on Twitter](https://twitter.com/typo3_security)
* [#announce channel on Slack](https://typo3.org/community/meet/how-to-use-slack-in-the-typo3-community)
* [TYPO3 Announce Mailing List](https://lists.typo3.org/cgi-bin/mailman/listinfo/typo3-announce)

The TYPO3 Security Team takes care of requesting [CVE IDs](https://www.cve.org/About/Process#CVERecordLifecycle)
(Common Vulnerabilities and Exposures identifiers).

## TYPO3 Release Dates ("Patchday")

We aim to publish TYPO3 maintenance releases on Tuesdays as a general rule.
However, exceptions apply (e.g. public holidays). Release dates of
[maintenance releases](https://typo3.org/cms/roadmap/maintenance-releases)
are scheduled in advance. These releases can contain security fixes.

## Further Information

* [TYPO3 Security Team](https://typo3.org/community/teams/security)
* [TYPO3 Security Advisories](https://typo3.org/help/security-advisories)
* [TYPO3 Security Guidelines](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/Security/Index.html)
