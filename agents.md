# agents.md — twofactor_totp

## Repository Overview

An ownCloud Server (OC10) app providing TOTP (Time-based One-Time Password) second-factor authentication. Compatible with Google Authenticator, Authy and other standard TOTP apps.

- **Classification:** Classic (OC10)
- **Activity Status:** Active
- **License:** AGPL-3.0 (license file is `COPYING`)
- **Language:** PHP

## Architecture & Key Paths

- `appinfo/` — ownCloud app metadata (info.xml, routes)
- `lib/` — PHP backend (TOTP generation, verification, providers)
- `js/` — Frontend JavaScript (QR code display, settings UI)
- `l10n/` — Localization/translation files
- `templates/` — PHP templates for settings UI
- `screenshots/` — UI screenshots for documentation
- `tests/` — PHPUnit unit and integration tests
- `Makefile` — Build and test orchestration
- `composer.json` — PHP dependencies
- `phpcs.xml` — Code style configuration
- `phpstan.neon` — Static analysis configuration
- `phpunit.xml` — Unit test configuration
- `COPYING` — AGPL-3.0 license file
- `AUTHORS.md` — List of contributors

## Development Conventions

- Standard ownCloud OC10 app structure
- Code style enforced by phpcs (`phpcs.xml`)
- Static analysis via PHPStan
- SonarCloud integration
- License file is named `COPYING` (not `LICENSE`)

## Build & Test Commands

```bash
make test                   # Run all tests
make test-php-unit          # Run PHP unit tests
make test-php-style         # Check code style (phpcs)
make test-php-style-fix     # Auto-fix code style issues
make test-php-phan          # Run Phan static analysis
make test-php-phpstan       # Run PHPStan static analysis
make dist                   # Build distribution package
make clean                  # Clean build artifacts
phpunit -c phpunit.xml      # Run unit tests directly
phpunit -c phpunit.integration.xml  # Run integration tests
```

## Important Constraints

- **AGPL-3.0 copyleft license:** This repository is AGPL-3.0. The OSPO Apache 2.0 migration requires auditing copyleft dependencies and contributor agreements before relicensing.
- **Security-sensitive:** Handles TOTP secret generation and verification -- changes must be carefully reviewed.
- **Companion app:** Works alongside `twofactor_backup_codes` for a complete 2FA solution with recovery.
- **License file naming:** The license file is `COPYING`, not `LICENSE`.


## OSPO Policy Constraints

### GitHub Actions
- **Only** use actions owned by `owncloud`, created by GitHub (`actions/*`), verified on the GitHub Marketplace, or verified by the ownCloud Maintainers.
- Pin all actions to their full commit SHA (not tags): `uses: actions/checkout@<SHA> # vX.Y.Z`
- Never introduce actions from unverified third parties.

### Dependency Management
- Dependabot is configured for automated dependency updates.
- Review and merge Dependabot PRs as part of regular maintenance.
- Do not introduce new dependencies without discussion in an issue first.

### Git Workflow
- **Rebase policy**: Always rebase; never create merge commits. Use `git pull --rebase` and `git rebase` before pushing.
- **Signed commits**: All commits **must** be PGP/GPG signed (`git commit -S -s`).
- **DCO sign-off**: Every commit needs a `Signed-off-by` line (`git commit -s`).
- **Conventional Commits & Squash Merge**: Use the [Conventional Commits](https://www.conventionalcommits.org/) format where the repository enforces it. Many repos use squash merge, where the PR title becomes the commit message on the default branch — apply Conventional Commits format to PR titles as well. A reusable GitHub Actions workflow enforces this.

## Context for AI Agents

- This is an ownCloud Server (OC10) app, not an oCIS extension.
- The `lib/` directory contains TOTP generation, secret storage and verification logic.
- Users configure TOTP through their personal settings page.
- QR code generation for authenticator app enrollment is handled in `js/`.
- The `l10n/` directory provides translations.
