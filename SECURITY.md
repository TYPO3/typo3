# Security Policy

## Supported Versions

The following matrix shows the versions currently maintained by the
TYPO3 Community. Sprint releases (versions before 12.4.0 and 11.5.0,
in their corresponding branches) are neither maintained nor supported.

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

Please report possible vulnerabilities to [security@typo3.org](mailto:security@typo3.org)

* Name the affected project (either TYPO3 Core or a TYPO3 extension/plugin)
* Name the exact version or version range that has been analysed
* Provide a step-by-step description of how to exploit the potential vulnerability

### Coordinated Disclosure

The [TYPO3 Security Team](https://typo3.org/community/teams/security) will
coordinate with core mergers or corresponding extension/plugin maintainers and
other affected parties. When a security fix is ready, we will package new
releases and announce the fix to the public using various communication channels like:

* [TYPO3 Security Advisories](https://typo3.org/help/security-advisories)
* [TYPO3 Security Team on Twitter](https://twitter.com/typo3_security)
* [#announce channel on Slack](https://typo3.org/community/meet/how-to-use-slack-in-the-typo3-community)
* [TYPO3 Announce Mailing List](http://lists.typo3.org/cgi-bin/mailman/listinfo/typo3-announce)

The TYPO3 Security Team is taking care of requesting CVE IDs (common vulnerability and exposer identifiers).
Please do not post or publish vulnerabilities to public issue trackers or discuss them on Slack or Twitter.

### Message Encryption

It is possible to send GPG/PGP encrypted emails to [security@typo3.org](mailto:security@typo3.org) using key id
`C05FBE60` (complete fingerprint `B41C C3EF 373E 0F5C 7018  7FE9 3BEF BD27 C05F BE60`):

* download [public key file from typo3.org](https://typo3.org/fileadmin/t3o_common_storage/keys/B41CC3EF373E0F5C70187FE93BEFBD27C05FBE60.asc)
* download [public key file from keys.openpgp.org](https://keys.openpgp.org/vks/v1/by-fingerprint/B41CC3EF373E0F5C70187FE93BEFBD27C05FBE60)

## TYPO3 Release Dates / "Patchday"

TYPO3 releases (including possible security fixes) are usually published
on Tuesdays (except on holidays like Christmas or New Year).

The [Maintenance Releases](https://typo3.org/cms/roadmap/maintenance-releases)
for stable versions have been scheduled in advance - it is very likely that
security fixes will also be released on these dates.
