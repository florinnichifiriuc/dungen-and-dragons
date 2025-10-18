<?php

namespace App\Services;

use Illuminate\Support\Arr;

class ConditionMentorPromptManifest
{
    protected string $manifestFilename;

    protected string $defaultLocale;

    /**
     * @var array<string, array<string, mixed>>
     */
    protected array $cache = [];

    public function __construct(?string $manifestFilename = null, ?string $defaultLocale = null)
    {
        $this->manifestFilename = $manifestFilename
            ?? (string) config('condition-transparency.mentor_briefings.prompt_manifest.filename', 'transparency-ai.json');
        $this->defaultLocale = $defaultLocale
            ?? (string) config('condition-transparency.mentor_briefings.prompt_manifest.default_locale', 'en');
    }

    /**
     * @return array<string, mixed>
     */
    public function manifest(string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();

        if (isset($this->cache[$locale])) {
            return $this->cache[$locale];
        }

        $manifest = $this->loadManifestForLocale($locale);

        if ($manifest === [] && $locale !== $this->defaultLocale) {
            $manifest = $this->loadManifestForLocale($this->defaultLocale);
        }

        return $this->cache[$locale] = $manifest;
    }

    /**
     * @return array<string, mixed>
     */
    public function mentorBriefing(string $locale = null): array
    {
        return (array) Arr::get($this->manifest($locale), 'mentor_briefings', []);
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<int, string>
     */
    public function toneTags(array $manifest): array
    {
        $tags = Arr::get($manifest, 'tone_tags', []);

        if (! is_array($tags)) {
            return [];
        }

        return array_values(array_filter(array_map('strval', $tags)));
    }

    /**
     * @param  array<string, mixed>  $manifest
     * @return array<string, array<string, mixed>>
     */
    public function sections(array $manifest): array
    {
        $sections = Arr::get($manifest, 'sections', []);

        if (! is_array($sections)) {
            return [];
        }

        return array_map(function ($definition) {
            return is_array($definition) ? $definition : [];
        }, $sections);
    }

    protected function loadManifestForLocale(string $locale): array
    {
        $path = resource_path(sprintf('lang/%s/%s', $locale, $this->manifestFilename));

        if (! is_file($path)) {
            return [];
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            return [];
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : [];
    }
}
