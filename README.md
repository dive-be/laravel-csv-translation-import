# Laravel Lingo

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dive-be/laravel-lingo.svg?style=flat-square)](https://packagist.org/packages/dive-be/laravel-lingo)

⚠️ Minor releases of this package may cause breaking changes as it has no stable release yet.

## What problem does this package solve?

Sometimes, clients will use translation services and supply CSV files with translations. You can use this package to import those translations.

## Installation

You can install the package via composer:

```bash
composer require dive-be/laravel-lingo
```

This is the contents of the published config file:

```php
return [
    'exclude' => [],
];
```

## Usage

A common usage is to load or export specific translations from a CSV file. 

### CSV file constraints (for importing translations)

* The CSV file must have headers corresponding to the different locales.
* The translation key's header must be `key`.
* By default, you must use a semicolon (`;`) to separate columns. (You can modify the delimiter character.)
* You cannot have duplicate column names (you may wish to rename empty columns after exporting from Excel or Numbers).

A valid file looks like this:

```csv
key,nl,en
auth-login.title;Aanmelden;Log In
auth-login.description;Vul hieronder je gegevens in.;Fill in your details below.
```

### Example usage

#### Saving loaded translations to your local Laravel project

You can take the loaded translations and save them to your local Laravel project.

```php
Lingo::make()
    ->parseFile('/path/to/translations.csv', 'nl')
    ->persist('nl');
```

You may wish to load the existing translations from your Laravel app first, and then save (overwrite) the merged list of translations:

```php
Lingo::make()
    ->load('nl')
    ->parseFile('/path/to/translations.csv', 'nl')
    ->persist('nl');
```

If you do not want to override translation keys that already exist (and only import new ones), you can configure this when parsing the file:

```php
Lingo::make()
    ->load('es')
    ->parseFile(
        filePath: '/path/to/translations.csv', 
        locales: 'es',
        replacingExistingValues: false,
    )
    ->persist('es');
```

#### Exporting to a CSV file

Another common use case is exporting your translations to a CSV file, so you can send these to a client or translator. 

**Note**: If you have keys for translations that only exist for a given language, you may wish to _load all_ translations, and export for all languages. This way you can get the union of all translation keys across all those languages, along with the localized version. If translations are missing, those fields will be left empty.

This can easily be accomplished like this:

```php
Lingo::make()
    ->load($languages = ['en', 'nl', 'fr'])
    ->exportToCsvFile('path/to/output.csv', $languages);
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
