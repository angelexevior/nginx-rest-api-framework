# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [1.0.0]

### Added
- Interactive `install.sh` for database setup, sample data import, and
  nginx/Apache vhost configuration.
- PHPUnit test suite covering the request/response envelope and the sample
  `Country` data class.
- GitHub Actions CI (lint + tests across PHP 7.4/8.1/8.3).
- `LICENSE` (MIT), `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, issue/PR templates.

### Changed
- Renamed the config file/class to `config.php`/`Config`.
- Rewrote the README to describe what's actually in the repo.
- Fixed POST/PUT/DELETE request handling in `index.php`, which never
  matched real HTTP methods and always fell through to the GET/default
  branch.
- Fixed a SQL injection in `Country::getCountries`/`getCurrencies`.
- Fixed PHP 8 incompatibilities (`get_magic_quotes_gpc`, `mysqli_error`
  without a connection argument, PHP4-style constructors).
- Renamed `htaccess.txt` back to `.htaccess` so Apache rewrites work.

### Removed
- Dead/duplicate code left over from an earlier fork (`classes/` directory)
  and unrelated production-app remnants (hardcoded internal server IPs,
  SendGrid credential wiring, sphinx/memcache glue) that had been mixed
  into the framework.
