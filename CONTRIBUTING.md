# Contributing

Thanks for considering a contribution to nginx-rest-api-framework!

## Getting set up

```
git clone https://github.com/angelexevior/nginx-rest-api-framework.git
cd nginx-rest-api-framework
composer install
./install.sh   # configures the database and (optionally) your web server
```

## Making a change

1. Fork the repo and create a branch off `master`.
2. Make your change. Keep pull requests focused on a single concern.
3. Run the checks locally:
   ```
   composer test          # PHPUnit
   find . -name "*.php" -not -path "./vendor/*" -print0 | xargs -0 -n1 php -l
   ```
4. Open a pull request describing what changed and why. CI (lint + tests
   against PHP 7.4/8.1/8.3) runs automatically on every PR.

## Code style

- Match the existing style in the file you're editing.
- Prefer small, explicit functions over clever one-liners.
- New controllers extend `AbstractController` and implement `get`/`post`/`put`/`delete` - see the README's "Writing your own controller" section.

## Reporting bugs / requesting features

Please use the issue templates - they ask for just enough detail (PHP
version, web server, steps to reproduce) to act on the report quickly.
