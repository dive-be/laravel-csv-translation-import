<?php declare(strict_types=1);

namespace Dive\Lingo;

use Illuminate\Support\Facades\File;
use Symfony\Component\VarExporter\Exception\ExceptionInterface;
use Symfony\Component\VarExporter\VarExporter;
use League\Csv\Reader;
use League\Csv\Writer;

class Lingo
{
    public static function make(...$args)
    {
        return new static(...$args);
    }

    /** @var array<string, array> */
    private array $translations = [];

    /**
     * Attempts to load Laravel translations for the given locale(s).
     * If the $path is null, the translations are loaded from the `lang_path()`.
     */
    public function load(string|array $onlyLocales, ?string $path = null): self
    {
        $locales = is_array($onlyLocales) ? $onlyLocales : [$onlyLocales];

        foreach ($locales as $locale) {
            // TODO: Instead of replacing all values for a given locale, merge the values in
            $this->translations[$locale] = Translations::get($locale, $path)->toArray();
        }

        return $this;
    }

    /**
     * Attempts to load translations from a CSV file.
     */
    public function parseFile(
        string $filePath,
        string|array $onlyLocales,
        string $csvDelimiter = ';',
        int $headerOffset = 0,
        bool $replacingExistingValues = true,
    ): self {
        $csv = Reader::createFromPath($filePath);

        $this->parseCsv($csv, $onlyLocales, $csvDelimiter, $headerOffset, $replacingExistingValues);

        return $this;
    }

    /**
     * Attempts to load translations from a string that is formatted as a CSV.
     */
    public function parseString(
        string $content,
        string|array $onlyLocales,
        string $csvDelimiter = ';',
        bool $replacingExistingValues = true,
    ): self {
        $csv = Reader::createFromString($content);

        $this->parseCsv($csv, $onlyLocales, $csvDelimiter, 0, $replacingExistingValues);

        return $this;
    }

    /**
     * Sort translations alphabetically, by key.
     */
    public function sort($ascending = true): self
    {
        foreach ($this->translations as &$values) {
            $ascending ? ksort($values) : krsort($values);
        }

        return $this;
    }

    /**
     * Persist the translations for the chosen locale(s).
     * If the target directory isn't set, this will (over)write translations into the current Laravel app's lang path.
     *
     * @throws \Exception
     * @throws ExceptionInterface
     */
    public function persist(string|array $locales, ?string $baseDirectory = null): self
    {
        $basePath = $baseDirectory ?? lang_path();

        $locales = is_array($locales) ? $locales : [$locales];

        foreach ($this->translations as $locale => $translations) {
            if (!in_array($locale, $locales)) {
                break;
            }

            $localeDir = $basePath . '/' . $locale;
            if (! File::isDirectory($localeDir)) {
                File::makeDirectory($localeDir, 493, true);
            }

            $translations = collect($translations)->map(function ($translation, $key) {
                $segments = explode('-', $key, 2);

                if (count($segments) == 1) {
                    throw new \Exception("The key `$key` is invalid: it must contain a separator character (`-`).");
                }

                return (object) [
                    'file' => $segments[0],
                    'key' => $segments[1],
                    'value' => $translation,
                ];
            })->values()->groupBy('file')->toArray();

            foreach ($translations as $file => $items) {
                if (preg_match('/(.*)\/[^\/]+$/', $file, $matches)) {
                    $path = $basePath . '/' . $locale . '/' . $matches[1];
                    if (! File::isDirectory($path)) {
                        File::makeDirectory($path, 493, true);
                    }
                }

                $map = collect($items)->mapWithKeys(function ($item) {
                    return [$item->key => $item->value];
                })->undot()->toArray();

                $sourceCode = "<?php declare(strict_types=1);\n\nreturn "
                    . VarExporter::export($map) . ';' . PHP_EOL;

                File::put(
                    $basePath . '/' . $locale . '/' . $file . '.php',
                    $sourceCode
                );
            }
        }

        return $this;
    }

    /**
     * Writes the contents of all the translations to a CSV file at the given path.
     */
    public function exportToCsvFile(string $path, string|array $locales, $delimiter = ";"): self
    {
        $csv = Writer::createFromPath($path, 'w+');
        $this->exportToCsv($csv, $locales, $delimiter);
        return $this;
    }

    /**
     * Returns an array containing all the translations, grouped by locale.
     */
    public function toArray(): array
    {
        return $this->translations;
    }

    /**
     * Returns a string representation containing all the translations for a given locale.
     * The string output is the same as the contents of a CSV file would be.
     */
    public function toCsvString(string|array $locales, $delimiter = ";"): string
    {
        $csv = Writer::createFromString();
        $this->exportToCsv($csv, $locales, $delimiter);
        return $csv->toString();
    }

    private function parseCsv(
        Reader $csv,
        string|array $locales,
        string $csvDelimiter,
        int $headerOffset,
        bool $replacingExistingValues,
    ): void {
        $locales = is_array($locales) ? $locales : [$locales];

        $csv->setDelimiter($csvDelimiter);
        $csv->setHeaderOffset($headerOffset);

        foreach ($locales as $locale) {
            if (! array_key_exists($locale, $this->translations)) {
                $this->translations[$locale] = [];
            }
        }

        foreach ($csv->getRecords() as $index => $record) {
            if ($headerOffset > $index) { // Ensure that rows before the header offset are ignored
                continue;
            }

            foreach ($locales as $locale) {
                if (! array_key_exists($locale, $record)) {
                    $this->translations[$locale][$record['key']] = '';
                    break;
                }

                if (array_key_exists($record['key'], $this->translations[$locale])
                    && ! $replacingExistingValues
                ) {
                    break;
                }

                if (empty(trim($record['key']))) {
                    break;
                }

                $this->translations[$locale][trim($record['key'])] = trim($record[$locale]);
            }
        }
    }

    private function exportToCsv(
        Writer $csv,
        string|array $locales,
        $delimiter = ";"
    ): void {
        $locales = is_array($locales) ? $locales : [$locales];

        $data = [];

        foreach ($locales as $locale) {
            if (array_key_exists($locale, $this->translations)) {
                foreach ($this->translations[$locale] as $key => $value) {
                    $data[$key][$locale] = $value;
                }
            }
        }

        $rows = collect($data)
            ->map(function ($value, $key) use ($locales) {
                return collect($key)->merge(collect($locales)
                    ->map(fn ($locale) => array_key_exists($locale, $value)
                        ? $value[$locale] : '')
                    ->toArray());
            })
            ->values()
            ->toArray();

        $csv->setDelimiter($delimiter);
        $csv->insertOne(array_merge(['key'], $locales));
        $csv->insertAll($rows);
    }
}
