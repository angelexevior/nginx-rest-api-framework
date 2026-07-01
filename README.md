# nginx-rest-api-framework

[![CI](https://github.com/angelexevior/nginx-rest-api-framework/actions/workflows/ci.yml/badge.svg)](https://github.com/angelexevior/nginx-rest-api-framework/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](composer.json)

A minimal, dependency-free REST API framework in PHP. It maps URLs like
`/[resource]/[id]` to a controller class and returns a consistent JSON
envelope, without requiring any external packages.

> This is a small, educational framework — a good starting point for a
> lightweight PHP API, not a production-grade framework like Laravel/Symfony.

## How it works

- `index.php` is the single front controller. Every request is rewritten to
  it (see `nginx-config.txt` / `.htaccess`).
- The first URL segment is mapped to `{Segment}Controller` and autoloaded
  from `controllers/` (falling back to `models/` for framework classes).
- The HTTP method (`get`, `post`, `put`, `delete`) is called on that
  controller with a `Request` object.
- The return value is wrapped in a JSON envelope by `ResponseJson`:

  ```json
  {
      "success": true,
      "request": { "method": "GET", "controller": "platform", "resource": "countries", "...": "..." },
      "error": { "errorid": 0, "message": null },
      "data": [ { "...": "..." } ]
  }
  ```

## Requirements

- PHP 7.4+ (uses `mysqli`)
- MySQL/MariaDB
- nginx or Apache

## Installation

### Quick start

Run the interactive installer from the project root:

```
./install.sh
```

It checks prerequisites, configures `config.php`, imports `countries.sql`,
and (optionally) writes and enables an nginx or Apache vhost for you. It
never overwrites files or reloads a web server without asking first.

### Manual steps

1. Deploy the files to your web server.
2. Import `countries.sql` into your database.
3. Edit `config.php` and provide your database credentials.
4. Point your web server rewrite rules at `index.php`:
   - **nginx**: see `nginx-config.txt`
   - **Apache**: `.htaccess` is included

## Sample requests

```
GET /platform/countries          -> all published countries
GET /platform/countries/1        -> country with id 1
GET /platform/currencies         -> all currencies
```

## Writing your own controller

Create a class in `controllers/` extending `AbstractController` and
implement `get`/`post`/`put`/`delete`:

```php
class WidgetsController extends AbstractController {
    public function get($request) {
        return array('widgets' => array());
    }
    public function post($request) { return $this->error('Not implemented'); }
    public function put($request) { return $this->error('Not implemented'); }
    public function delete($request) { return $this->error('Not implemented'); }
}
```

Requests to `/widgets` will now be routed to it. Override
`AbstractController::authorize()` to add API key/token checks.

## Project layout

```
index.php                    Front controller / router
controllers/                 Your API controllers
models/                      Framework request/response classes
library/classes/Beep.php     Database connection helper
library/classes/Country.php  Sample data-access class used by PlatformController
countries.sql                Sample dataset for the /platform/countries endpoint
tests/                       PHPUnit test suite
```

## Testing

```
composer install
composer test
```

CI runs the same suite (plus `php -l` linting) against PHP 7.4, 8.1 and 8.3
on every push and pull request.

## Contributing

Contributions are welcome - see [CONTRIBUTING.md](CONTRIBUTING.md) for how
to get set up and run the checks locally. Please also review the
[Code of Conduct](CODE_OF_CONDUCT.md).

See [CHANGELOG.md](CHANGELOG.md) for release history.

## License

MIT, see [LICENSE](LICENSE).
