# Security Policy

## Supported Versions

The following matrix shows the versions that are currently maintained
by the TYPO3 Community. Sprint releases (versions before 10.4.0 and
before 9.5.0, in their corresponding branches) are not maintained nor
supported.

| Version         | Supported          |
| --------------- | ------------------ |
|          10.4.x | :white_check_mark: |
|          10.3.x | :x:                |
|          10.2.x | :x:                |
|          10.1.x | :x:                |
|          10.0.x | :x:                |
|           9.5.x | :white_check_mark: |
|         < 9.5.0 | :x:                |

## Reporting a Vulnerability

Please report potential vulnerabilities to [security@typo3.org](mailto:security@typo3.org)

* mention the project that is affected (either TYPO3 core or a TYPO3 extension/plugin)
* mention the exact version or version range that has been analyzed
* provide a step-by-step description on how to exploit the potential vulnerability

### Coordinated Disclosure

The TYPO3 Security Team will coordinate with core mergers or corresponding
extension/plugin maintainers and other affected parties. If a security fix
is ready, we then will package new releases and announce the fix to the
public using various communication channels like:

* [TYPO3 Security Advisories](https://typo3.org/help/security-advisories)
* [TYPO3 Security Team on Twitter](https://twitter.com/typo3_security)
* [#announce channel on Slack](https://typo3.org/community/meet/how-to-use-slack-in-the-typo3-community)
* [TYPO3 Announce Mailing List](http://lists.typo3.org/cgi-bin/mailman/listinfo/typo3-announce)

The TYPO3 Security Team is taking care of requesting CVE IDs (common vulnerability and exposer identifiers).
Please do not post or publish vulnerabilties to public issue trackers or discuss it on Slack or Twitter.

### Message Encryption

It is possible to send GPG/PGP encrypted emails to security@typo3.org using key id
`C05FBE60` (complete fingerprint `B41C C3EF 373E 0F5C 7018  7FE9 3BEF BD27 C05F BE60`):

* download [public key file from typo3.org](https://typo3.org/fileadmin/t3o_common_storage/keys/B41CC3EF373E0F5C70187FE93BEFBD27C05FBE60.asc)
* download [public key file from keys.openpgp.org](https://keys.openpgp.org/vks/v1/by-fingerprint/B41CC3EF373E0F5C70187FE93BEFBD27C05FBE60)

## TYPO3 Release Dates / "Patchday"

TYPO3 releases (including potential security fixes) are usually released
on Tuesdays (except for holidays like Christmas or New Year's Day).

[Maintenance releases](https://typo3.org/cms/roadmap/maintenance-releases)
for stable versions have been scheduled in advance - it is very likely that
security fixes are released during these dates as well.
