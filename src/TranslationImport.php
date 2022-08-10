<?php declare(strict_types=1);

namespace Dive\TranslationImport;

use Illuminate\Support\Facades\File;
use Symfony\Component\VarExporter\Exception\ExceptionInterface;
use Symfony\Component\VarExporter\VarExporter;
use League\Csv\Reader;
use Exception;

class TranslationImport
{
    public static function make(...$arguments)
    {
        return new static(...$arguments);
    }

    /** @var array<string, array> */
    private array $translations = [];

    private bool $replacesExistingValues = true;

    public function configure(bool $replacesExistingValues): self
    {
        $this->replacesExistingValues = $replacesExistingValues;

        return $this;
    }

    public function load(string|array $onlyLocales): self
    {
        $locales = is_array($onlyLocales) ? $onlyLocales : [$onlyLocales];

        foreach ($locales as $locale) {
            $this->translations[$locale] = Translations::get($locale)->toArray();
        }

        return $this;
    }

    public function parseFile(string $filePath, string|array $onlyLocales, $csvDelimiter = ';'): self
    {
        $csv = Reader::createFromPath($filePath);

        $this->parseCsv($csv, $onlyLocales, $csvDelimiter);

        return $this;
    }

    public function parseString(string $content, string|array $onlyLocales, $csvDelimiter = ';'): self
    {
        $csv = Reader::createFromString($content);

        $this->parseCsv($csv, $onlyLocales, $csvDelimiter);

        return $this;
    }

    public function parseCsv(Reader $csv, string|array $onlyLocales, $csvDelimiter = ';'): self
    {
        $locales = is_array($onlyLocales) ? $onlyLocales : [$onlyLocales];

        $csv->setDelimiter($csvDelimiter);
        $csv->setHeaderOffset(0);

        foreach ($locales as $locale) {
            if (! array_key_exists($locale, $this->translations)) {
                $this->translations[$locale] = [];
            }
        }

        foreach ($csv->getRecords() as $record) {
            foreach ($locales as $locale) {
                if (! array_key_exists($locale, $record)) {
                    $this->translations[$locale][$record['key']] = '';
                    break;
                }

                if (array_key_exists($record['key'], $this->translations[$locale])
                    && ! $this->replacesExistingValues
                ) {
                    break;
                }
                
                if (empty(trim($record['key']))) {
                    break;
                }

                $this->translations[$locale][$record['key']] = $record[$locale];
            }
        }

        return $this;
    }

    public function toArray(): array
    {
        return $this->translations;
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
}
