<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Short-command to get the current language name
 *
 * @return String
 */
function getLang(): string
{
    static $CI;

    if (!$CI) {
        $CI = &get_instance();
    }

    return $CI->language->getLanguage();
}

/**
 * Short-command to get a language string
 *
 * @param String $id
 * @param String $file
 * @return mixed
 */
function lang(string $id, string $file = 'main'): mixed
{
    static $CI;

    if (!$CI) {
        $CI = &get_instance();
    }

    return $CI->language->get($id, $file);
}

/**
 * Short-command to set a client language string
 *
 * @param String $id
 * @param String $file
 * @return void
 */
function clientLang(string $id, string $file = 'main'): void
{
    static $CI;

    if (!$CI) {
        $CI = &get_instance();
    }

    $CI->language->setClientData($id, $file);
}

/**
 * Translate the JSON-stored language string to the desired language
 *
 * @param String $json
 * @return String
 */
function langColumn(string $json): string
{
    static $CI;

    if (!$CI) {
        $CI = &get_instance();
    }

    return $CI->language->translateLanguageColumn($json);
}

/**
 * Get the selected language
 *
 * @param String $json
 * @return String
 */
function getColumnLang(string $json): string
{
    static $CI;

    if (!$CI) {
        $CI = &get_instance();
    }

    return $CI->language->getColumnLanguage($json);
}
