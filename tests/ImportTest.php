<?php declare(strict_types=1);

namespace Tests\Unit\App\Artisan\Import;

use Dive\TranslationImport\TranslationImport;
use Illuminate\Support\Facades\Storage;
use function Pest\testDirectory;

it('can load existing translations (and loading strips newlines)', function () {
    $translations = TranslationImport::make()
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
    $translations = TranslationImport::make()
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

    $translations = TranslationImport::make()
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

it('can source translations from a file', function () {
    $translations = TranslationImport::make()
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

it('skips keys that are empty', function () {
    $csv = <<<CSV
        key;nl;es
        b2b/ticket-summary;Overzicht;Resumen
        ;empty;key
        CSV;

    $translations = TranslationImport::make()
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

    $translations = TranslationImport::make()
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

    $translit = TranslationImport::make()
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

    $translit = TranslationImport::make()
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

    $translit
        ->configure(replacesExistingValues: false)
        ->parseString($csv2, ['nl']);

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

    $path = $disk->path('');

    TranslationImport::make()
        ->parseString($csv, 'nl')
        ->persist('nl', $path);

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
        file_get_contents($path . '/nl/b2b/ticket.php')
    );
});
