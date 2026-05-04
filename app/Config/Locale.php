<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Locale extends BaseConfig
{
    /**
     * The default locale for the application.
     */
    public string $defaultLocale = 'en';

    /**
     * Whether to automatically negotiate the locale from the request.
     */
    public bool $negotiateLocale = true;

    /**
     * Supported locales for the application.
     * This is a minimal set and may be extended as needed.
     *
     * @var list<string>
     */
    public array $supportedLocales = [
        'en',
        'es-ES',
        'es-MX',
        'pt-BR',
        'fr',
        'de-DE',
    ];

    /**
     * The current default locale used by the runtime.
     */
    protected static ?string $currentLocale = null;

    public static function getDefault(): string
    {
        if (static::$currentLocale !== null) {
            return static::$currentLocale;
        }

        $locale = new static();

        return static::$currentLocale = $locale->defaultLocale;
    }

    public static function setDefault(string $locale): void
    {
        static::$currentLocale = $locale;
    }
}
