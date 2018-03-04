<?php
declare(strict_types=1);

namespace LiveXYZ;

use pocketmine\plugin\Plugin;
use pocketmine\Server;

class LocalizationService
{

    static $fallbackLocale;
    static $forceLanguage;

    static $packages = [];

    /**
     * @param Plugin $plugin
     * @param string $languageDirectoryPath
     * @throws \Exception
     */
    public static function addPackage(Plugin $plugin, string $languageDirectoryPath): void
    {
        $languageDirectoryPath = rtrim($languageDirectoryPath, '/') . '/';
        if (empty(self::$fallbackLocale)) {
            self::initLanguageSettings();
        }
        if (!is_dir($languageDirectoryPath)) {
            throw new \Exception('Directory ' . $languageDirectoryPath . ' not found.');
        }

        $allFiles = \scandir($languageDirectoryPath, SCANDIR_SORT_NONE);
        $files = \array_filter($allFiles, function ($filename) {
            return \substr($filename, -4) === ".ini";
        });
        $result = [];
        foreach ($files as $file) {
            $strings = [];
            $filePath = $languageDirectoryPath . $file;
            self::loadLang($filePath, $strings);
            $result[\substr($file, 0, -4)] = $strings;
        }

        self::$packages[$plugin->getName()] = $result;
    }

    public static function translate(string $language, string $packageName, string $key, array $parameters = [], ?string $default = null): string
    {
        if (self::$forceLanguage || !isset(self::$packages[$packageName][$language][$key])) {
            $language = self::$fallbackLocale;
        }
        if (!isset(self::$packages[$packageName][$language][$key])) {
            $message = $default;
        } else {
            $message = self::$packages[$packageName][$language][$key];
        }
        return sprintf($message, ...$parameters);
    }

    private static function initLanguageSettings()
    {
        static::$fallbackLocale = Server::getInstance()->getProperty("settings.locale", 'en_US');
        static::$forceLanguage = Server::getInstance()->isLanguageForced();
    }

    protected static function loadLang(string $path, array &$d)
    {
        if (\file_exists($path)) {
            $d = \array_map('stripcslashes', \parse_ini_file($path, \false, INI_SCANNER_RAW));
            return \true;
        } else {
            return \false;
        }
    }

    protected static function log(string $message, string $level = \LogLevel::NOTICE)
    {
        Server::getInstance()->getLogger()->log($level, $message);
    }
}