<?php declare(strict_types=1);

namespace Tests\Unit\App\Artisan\Import;

use Dive\Lingo\Lingo;
use Illuminate\Support\Facades\Storage;
use function Pest\testDirectory;

it('can load existing translations (and loading strips newlines)', function () {
    $translations = Lingo::make()
        // if we want to start with our existing TL
        ->load(['en', 'fr'])
        ->toArray();

    $this->assertArrayHasKey('en', $translations);
    $this->assertArrayHasKey('fr', $translations);

    $this->assertEquals([
        'en' => [
            'passwords-reset' => 'Your password has been reset!',
            'passwords-sent' => 'We have emailed your password reset link!',
            'passwords-throttled' => 'Please wait before retrying.',
            'passwords-token' => 'This password reset token is invalid.',
            'passwords-user' => "We can't find a user with that email address.",
            'passwords-validation.failed' => 'Validation failed',
            'passwords-validation.passed' => 'Validation passed'
        ],
        'fr' => [
            'passwords-reset' => 'Votre mot de passe a été réinitialisé !!',
            'passwords-sent' => 'Nous vous avons envoyé par email le lien de réinitialisation du mot de passe !',
            'passwords-throttled' => 'Veuillez patienter avant de réessayer.',
            'passwords-token' => "Ce jeton de réinitialisation du mot de passe n'est pas valide.",
            'passwords-user' => "Aucun utilisateur n'a été trouvé avec cette adresse email.",
        ],
    ], $translations);
});

it('can load existing translations from separate directory', function () {
    $translations = Lingo::make()
        // we want to load the slightly altered alternate translations
        ->load(['en', 'fr'], testDirectory('Files/alternate'));

    $this->assertArrayHasKey('en', $translations->toArray());
    $this->assertArrayHasKey('fr', $translations->toArray());

    // Make sure that the slightly altered string is correct
    $this->assertEquals(
        'Votre mot de passe a été réinitialisé !',
        $translations->toArray()['fr']['passwords-reset']
    );

    // Load the normal translations (should revert to original directory)
    $translations->load('fr');

    // Make sure that the original string loaded back in
    $this->assertEquals(
        'Votre mot de passe a été réinitialisé !!',
        $translations->toArray()['fr']['passwords-reset']
    );
});

it('can populate the translations based on a csv file', function () {
    $csv = <<<CSV
        key;nl;es
        b2b/ticket-summary;Overzicht;Resumen
        b2b/ticket-total;Totaal;Total
        b2b/wishlist-add;Toevoegen aan favorieten;Añadir a favoritos
        CSV;

    $translations = Lingo::make()
        ->parseString($csv, ['nl'])
        ->toArray();

    $this->assertEquals([
        'nl' => [
            'b2b/ticket-summary' => 'Overzicht',
            'b2b/ticket-total' => 'Totaal',
            'b2b/wishlist-add' => 'Toevoegen aan favorieten',
        ],
    ], $translations);
});

it('trims the contents of the keys and values if needed', function () {
    $csv = <<<CSV
        key;nl;es
        b2b/ticket-summary  ;Overzicht;   Resumen
        b2b/ticket-total  ;   Totaal     ;Total
             b2b/wishlist-add;    Toevoegen aan favorieten   ;       Añadir a favoritos
        CSV;

    $translations = Lingo::make()
        ->parseString($csv, ['nl'])
        ->toArray();

    $this->assertEquals([
        'nl' => [
            'b2b/ticket-summary' => 'Overzicht',
            'b2b/ticket-total' => 'Totaal',
            'b2b/wishlist-add' => 'Toevoegen aan favorieten',
        ],
    ], $translations);
});

it('can sort the keys alphabetically (and reversed)', function () {
    $csv = <<<CSV
        key;en
        greeting.all;All
        greeting.welcome;Welcome
        greeting.goodbye;Farewell
        CSV;

    $translations = Lingo::make()
        ->parseString($csv, ['en']);

    $regular = $translations->sort(ascending: true)->toArray();

    $this->assertEquals('greeting.all', array_keys($regular['en'])[0]);
    $this->assertEquals('greeting.goodbye', array_keys($regular['en'])[1]);
    $this->assertEquals('greeting.welcome', array_keys($regular['en'])[2]);

    $reverse = $translations->sort(ascending: false)->toArray();

    $this->assertEquals('greeting.all', array_keys($reverse['en'])[2]);
    $this->assertEquals('greeting.goodbye', array_keys($reverse['en'])[1]);
    $this->assertEquals('greeting.welcome', array_keys($reverse['en'])[0]);
});

it('can source translations from a file', function () {
    $translations = Lingo::make()
        ->parseFile(testDirectory('Files/translations.csv'), ['nl'])
        ->toArray();

    $this->assertEquals([
        'nl' => [
            'b2b/ticket-summary' => 'Overzicht',
            'b2b/ticket-total' => 'Totaal',
            'b2b/wishlist-add' => 'Toevoegen aan favorieten',
        ],
    ], $translations);
});

it('can source translations from a file with a header offset', function () {
    $translations = Lingo::make()
        ->parseFile(testDirectory('Files/translations_header_offset.csv'), ['nl'], headerOffset: 2)
        ->toArray();

    $this->assertEquals([
        'nl' => [
            'b2b/ticket-summary' => 'Overzicht',
            'b2b/ticket-total' => 'Totaal',
            'b2b/wishlist-add' => 'Toevoegen aan favorieten',
        ],
    ], $translations);
});

it('skips keys that are empty', function () {
    $csv = <<<CSV
        key;nl;es
        b2b/ticket-summary;Overzicht;Resumen
        ;empty;key
        CSV;

    $translations = Lingo::make()
        ->parseString($csv, ['nl'])
        ->toArray();

    $this->assertEquals([
        'nl' => [
            'b2b/ticket-summary' => 'Overzicht',
        ],
    ], $translations);
});

it('populates keys as empty if locale is missing', function () {
    $csv = <<<CSV
        key;nl;es
        b2b/ticket-summary;Overzicht;Resumen
        b2b/ticket-total;Totaal;Total
        b2b/wishlist-add;Toevoegen aan favorieten;Añadir a favoritos
        CSV;

    $translations = Lingo::make()
        ->parseString($csv, ['fr'])
        ->toArray();

    $this->assertEquals([
        'fr' => [
            'b2b/ticket-summary' => '',
            'b2b/ticket-total' => '',
            'b2b/wishlist-add' => '',
        ],
    ], $translations);
});

it('replaces values when loading data if key already exists', function () {
    $csv = <<<CSV
        key;nl;es
        b2b/ticket-summary;Overzicht;Resumen
        b2b/ticket-total;Totaal;Total
        b2b/wishlist-add;Toevoegen aan favorieten;Añadir a favoritos
        CSV;

    $translit = Lingo::make()
        ->parseString($csv, ['nl']);

    $this->assertEquals([
        'nl' => [
            'b2b/ticket-summary' => 'Overzicht',
            'b2b/ticket-total' => 'Totaal',
            'b2b/wishlist-add' => 'Toevoegen aan favorieten',
        ],
    ], $translit->toArray());

    $csv2 = <<<CSV
        key;nl;es
        b2b/ticket-total;Totaal aantal records;Total
        CSV;

    $translit->parseString($csv2, ['nl']);

    $this->assertEquals([
        'nl' => [
            'b2b/ticket-summary' => 'Overzicht',
            'b2b/ticket-total' => 'Totaal aantal records',
            'b2b/wishlist-add' => 'Toevoegen aan favorieten',
        ],
    ], $translit->toArray());
});

it('does not replace values when loading data if behavior is modified', function () {
    $csv = <<<CSV
        key;nl;es
        b2b/ticket-summary;Overzicht;Resumen
        b2b/ticket-total;Totaal;Total
        b2b/wishlist-add;Toevoegen aan favorieten;Añadir a favoritos
        CSV;

    $translit = Lingo::make()
        ->parseString($csv, ['nl']);

    $this->assertEquals([
        'nl' => [
            'b2b/ticket-summary' => 'Overzicht',
            'b2b/ticket-total' => 'Totaal',
            'b2b/wishlist-add' => 'Toevoegen aan favorieten',
        ],
    ], $translit->toArray());

    $csv2 = <<<CSV
        key;nl;es
        b2b/ticket-total;Totaal aantal records;Total
        CSV;

    $translit->parseString(
        content: $csv2,
        onlyLocales: ['nl'],
        replacingExistingValues: false
    );

    $this->assertEquals([
        'nl' => [
            'b2b/ticket-summary' => 'Overzicht',
            'b2b/ticket-total' => 'Totaal',
            'b2b/wishlist-add' => 'Toevoegen aan favorieten',
        ],
    ], $translit->toArray());
});

it('can persist translations', function () {
    $csv = <<<CSV
        key;nl;en
        b2b/ticket-summary.title;Overzicht;Overview
        b2b/ticket-summary.description;Dit is het overzicht;This is the overview
        b2b/ticket-total;Totaal;Total
        CSV;

    $disk = Storage::fake('translations');

    Lingo::make()
        ->parseString($csv, 'nl')
        ->persist('nl', $disk->path(''));

    $expectedTranslationFile = <<<TL
    <?php declare(strict_types=1);

    return [
        'summary' => [
            'title' => 'Overzicht',
            'description' => 'Dit is het overzicht',
        ],
        'total' => 'Totaal',
    ];

    TL;

    $this->assertEquals(
        $expectedTranslationFile,
        $disk->get('/nl/b2b/ticket.php')
    );
});

it('can export data to a csv string', function () {
    $csv = <<<CSV
        key;nl;en
        b2b/ticket-summary.title;Overzicht;Overview
        b2b/ticket-summary.description;"Dit is het overzicht";"This is the overview"
        b2b/ticket-total;Totaal;Total\n
        CSV;

    $output = Lingo::make()
        ->parseString($csv, ['nl', 'en'])
        ->toCsvString(['nl', 'en']);

    $this->assertEquals($csv, $output);
});

it('can export data to a csv file', function () {
    $csv = <<<CSV
        key;nl;en
        b2b/ticket-summary.title;Overzicht;Overview
        b2b/ticket-summary.description;"Dit is het overzicht";"This is the overview"
        b2b/ticket-total;Totaal;Total\n
        CSV;

    $disk = Storage::fake('csv');

    Lingo::make()
        ->parseString($csv, ['nl', 'en'])
        ->exportToCsvFile($disk->path('export.csv'), ['nl', 'en']);

    $this->assertEquals($csv, $disk->get('export.csv'));
});

it('can export data sourced from Laravel translations to a csv file', function () {
    $csv = Lingo::make()
        ->load(['en', 'fr'], testDirectory('Files/key_mismatch'))
        ->toCsvString( ['en', 'fr']);

    // Note the empty values for FR's `passwords-reset` and EN's `passwords-throttled`!
    $expected = <<<CSV
    key;en;fr
    passwords-reset;"Your password has been reset!";
    passwords-sent;"We have emailed your password reset link!";"Nous vous avons envoyé par email le lien de réinitialisation du mot de passe !"
    passwords-throttled;;"Veuillez patienter avant de réessayer."\n
    CSV;

    $this->assertEquals($expected, $csv);
});
