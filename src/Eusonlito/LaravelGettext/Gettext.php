<?php
namespace Eusonlito\LaravelGettext;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use Gettext\GettextTranslator;
use Gettext\Extractors;
use Gettext\Generators;
use Gettext\Translations;
use Gettext\Translator;

class Gettext
{
    private static $locale;
    private static $config = array();
    private static $formats = array('php', 'mo', 'po');

    public static function setConfig(array $config)
    {
        if (!isset($config['native'])) {
            $config['native'] = false;
        }

        if (!isset($config['formats'])) {
            $config['formats'] = self::$formats;
        }

        self::$config = $config;
    }

    private static function getFile($locale)
    {
        return sprintf('%s/%s/LC_MESSAGES/%s.', self::$config['storage'], $locale, self::$config['domain']);
    }

    private static function getCache($locale)
    {
        if (is_file($file = self::getFile($locale).'po')) {
            return Extractors\Po::fromFile($file);
        }

        return false;
    }

    private static function store($locale, $entries)
    {
        $file = self::getFile($locale);
        $dir = dirname($file);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        Generators\Mo::toFile($entries, $file.'mo');
        Generators\Po::toFile($entries, $file.'po');
        Generators\PhpArray::toFile($entries, $file.'php');

        return $entries;
    }

    private static function scan()
    {
        Extractors\PhpCode::$functions = [
            '__' => '__',
            '_' => '__',
        ];

        $entries = new Translations();

        foreach (self::$config['directories'] as $dir) {
            if (!is_dir($dir)) {
                throw new Exception(__('Folder %s not exists. Gettext scan aborted.', $dir));
            }

            foreach (self::scanDir($dir) as $file) {
                if (strstr($file, '.blade.php')) {
                    $entries->mergeWith(Extractors\Blade::fromFile($file));
                } elseif (strstr($file, '.php')) {
                    $entries->mergeWith(Extractors\PhpCode::fromFile($file));
                }
            }
        }

        return $entries;
    }

    private static function scanDir($dir)
    {
        $directory = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory, RecursiveIteratorIterator::LEAVES_ONLY);

        $files = array();

        foreach ($iterator as $fileinfo) {
            $name = $fileinfo->getPathname();

            if (!strpos($name, '/.')) {
                $files[] = $name;
            }
        }

        return $files;
    }

    public static function getEntries($locale, $refresh = true)
    {
        if (empty($refresh) && ($cache = self::getCache($locale))) {
            return $cache;
        }

        $entries = clone self::scan();

        if (is_file($file = self::getFile($locale).'mo')) {
            $entries->mergeWith(Extractors\Mo::fromFile($file));
        }

        return $entries;
    }

    public static function setEntries($locale, $translations)
    {
        if (empty($translations)) {
            return true;
        }

        $entries = self::getCache($locale) ?: (new Translations());

        foreach ($translations as $msgid => $msgstr) {
            $msgid = urldecode($msgid);

            if (!($entry = $entries->find(null, $msgid))) {
                $entry = $entries->insert(null, $msgid);
            }

            $entry->setTranslation($msgstr);
        }

        self::store($locale, $entries);

        return $entries;
    }

    public static function load()
    {
        $locale = self::$locale.'.UTF-8';

        # IMPORTANT: locale must be installed in server!
        # sudo locale-gen es_ES.UTF-8
        # sudo update-locale

        putenv('LANG='.$locale);
        putenv('LANGUAGE='.$locale);
        putenv('LC_MESSAGES='.$locale);
        putenv('LC_PAPER='.$locale);
        putenv('LC_TIME='.$locale);
        putenv('LC_MONETARY='.$locale);

        setlocale(LC_MESSAGES, $locale);
        setlocale(LC_COLLATE, $locale);
        setlocale(LC_TIME, $locale);
        setlocale(LC_MONETARY, $locale);

        if (self::$config['native']) {
            self::loadNative($locale);
        } else {
            self::loadParsed($locale);
        }
    }

    private static function loadNative($locale)
    {
        $translator = new GettextTranslator();
        $translator->setLanguage($locale);
        $translator->loadDomain(self::$config['domain'], self::$config['storage']);

        bind_textdomain_codeset(self::$config['domain'], 'UTF-8');

        $translator->register();
    }

    private static function loadParsed($locale)
    {
        # Also, we will work with gettext/gettext library
        # because PHP gones crazy when mo files are updated

        bindtextdomain(self::$config['domain'], self::$config['storage']);
        bind_textdomain_codeset(self::$config['domain'], 'UTF-8');
        textdomain(self::$config['domain']);

        $file = dirname(self::getFile(self::$locale)).'/'.self::$config['domain'];

        $translations = null;

        foreach (self::$config['formats'] as $format) {
            if ($translations = self::loadFormat($format, $file)) {
                break;
            }
        }

        if ($translations === null) {
            $translations = new Translations();
        }

        Translator::initGettextFunctions((new Translator())->loadTranslations($translations));
    }

    private static function loadFormat($format, $file)
    {
        switch ($format) {
            case 'mo':
                return self::loadFormatMo($file);
            case 'po':
                return self::loadFormatPo($file);
            case 'php':
                return self::loadFormatPHP($file);
        }

        throw new Exception(sprintf('Format %s is not available', $format));
    }

    private static function loadFormatMo($file)
    {
        return is_file($file.'.mo') ? Translations::fromMoFile($file.'.mo') : null;
    }

    private static function loadFormatPo($file)
    {
        return is_file($file.'.po') ? Translations::fromPoFile($file.'.po') : null;
    }

    private static function loadFormatPHP($file)
    {
        return is_file($file.'.php') ? ($file.'.php') : null;
    }

    public static function setLocale($current, $new)
    {
        if (empty($current) || !in_array($current, self::$config['locales'])) {
            $current = self::$config['locales'][0];
        }

        if ($new && ($new !== $current) && in_array($new, self::$config['locales'])) {
            $current = $new;
        }

        self::$locale = $current;
    }

    public static function getLocale()
    {
        return self::$locale;
    }
}
