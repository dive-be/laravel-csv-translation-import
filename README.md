# Import translations from CSV files

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dive-be/laravel-csv-translation-import.svg?style=flat-square)](https://packagist.org/packages/dive-be/laravel-csv-translation-import)

⚠️ Minor releases of this package may cause breaking changes as it has no stable release yet.

## What problem does this package solve?

Sometimes, clients will use translation services and supply CSV files with translations. You can use this package to import those translations.

## Installation

You can install the package via composer:

```bash
composer require dive-be/laravel-csv-translation-import
```

This is the contents of the published config file:

```php
return [
    'exclude' => [],
];
```

## Usage

A common usage is to load specific translations from a CSV file. You can take the loaded translations and save them to your Laravel translations.

```php
TranslationImport::make()
    ->parseFile('/path/to/translations.csv', 'es')
    ->persist('es');
```

You may wish to load the existing translations first, and then save the merged list of translations:

```php
TranslationImport::make()
    ->load('es')
    ->parseFile('/path/to/translations.csv', 'es')
    ->persist('es');
```

If you do not want to override translation keys that already exist (and only import new ones), you can configure the `TranslationImport` instance, like this:

```php
TranslationImport::make()
    ->configure(replacesExistingValues: false)
    ->load('es')
    ->parseFile('/path/to/translations.csv', 'es')
    ->persist('es');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email oss@dive.be instead of using the issue tracker.

## Credits

- [Nico Verbruggen](https://github.com/dive-be)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.