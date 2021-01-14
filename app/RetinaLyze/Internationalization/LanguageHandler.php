<?php

/*
 * All Rights Reserved RetinaLyze System.
 */

namespace RetinaLyze\Internationalization;

use RetinaLyze\Utils\Config;

/**
 * Description of LanguageHandler
 *
 * @author mom
 */
class LanguageHandler {

    private $availableLangs;
    private $availableBrowserLangs;
    private $currentLang;

    function __construct($langID) {
        $this->availableBrowserLangs = array(
            "ar" => array("ar_AE", "Arabic (Standard)"),
            "ar_AE" => array("ar_AE", "Arabic (Standard)"),
            "ar-dz" => array("ar_AE", "Arabic (Algeria)"),
            "ar-bh" => array("ar_AE", "Arabic (Bahrain)"),
            "ar-eg" => array("ar_AE", "Arabic (Egypt)"),
            "ar-iq" => array("ar_AE", "Arabic (Iraq)"),
            "ar-jo" => array("ar_AE", "Arabic (Jordan)"),
            "ar-kw" => array("ar_AE", "Arabic (Kuwait)"),
            "ar-lb" => array("ar_AE", "Arabic (Lebanon)"),
            "ar-ly" => array("ar_AE", "Arabic (Libya)"),
            "ar-ma" => array("ar_AE", "Arabic (Morocco)"),
            "ar-om" => array("ar_AE", "Arabic (Oman)"),
            "ar-qa" => array("ar_AE", "Arabic (Qatar)"),
            "ar-sa" => array("ar_AE", "Arabic (Saudi Arabia)"),
            "ar-sy" => array("ar_AE", "Arabic (Syria)"),
            "ar-tn" => array("ar_AE", "Arabic (Tunisia)"),
            "ar-ae" => array("ar_AE", "Arabic (U.A.E.)"),
            "ar-ye" => array("ar_AE", "Arabic (Yemen)"),
            "ca" => array("es_ES", "Catalan"),
            "zh" => array("zh_CN", "Chinese"),
            "zh_CN" => array("zh_CN", "Chinese"),
            "zh-hk" => array("zh_CN", "Chinese (Hong Kong)"),
            "zh-cn" => array("zh_CN", "Chinese (PRC)"),
            "zh-sg" => array("zh_CN", "Chinese (Singapore)"),
            "zh-tw" => array("zh_CN", "Chinese (Taiwan)"),
            "da" => array("da_DK", "Danish"),
            "da_DK" => array("da_DK", "Danish"),
            "en" => array("en_US", "English"),
            "en_US" => array("en_US", "English"),
            "en-au" => array("en_US", "English (Australia)"),
            "en-bz" => array("en_US", "English (Belize)"),
            "en-ca" => array("en_US", "English (Canada)"),
            "en-ie" => array("en_US", "English (Ireland)"),
            "en-jm" => array("en_US", "English (Jamaica)"),
            "en-nz" => array("en_US", "English (New Zealand)"),
            "en-ph" => array("en_US", "English (Philippines)"),
            "en-za" => array("en_US", "English (South Africa)"),
            "en-tt" => array("en_US", "English (Trinidad & Tobago)"),
            "en-gb" => array("en_US", "English (United Kingdom)"),
            "en-us" => array("en_US", "English (United States)"),
            "en-zw" => array("en_US", "English (Zimbabwe)"),
            "fi" => array("fi_FI", "Finnish"),
            "fi_FI" => array("fi_FI", "Finnish"),
            "fr" => array("fr_FR", "French (Standard)"),
            "fr_FR" => array("fr_FR", "French (Standard)"),
            "fr-be" => array("fr_FR", "French (Belgium)"),
            "fr-ca" => array("fr_FR", "French (Canada)"),
            "fr-fr" => array("fr_FR", "French (France)"),
            "fr-lu" => array("fr_FR", "French (Luxembourg)"),
            "fr-mc" => array("fr_FR", "French (Monaco)"),
            "fr-ch" => array("fr_FR", "French (Switzerland)"),
            "de" => array("de_DE", "German (Standard)"),
            "de_DE" => array("de_DE", "German (Standard)"),
            "de-at" => array("de_DE", "German (Austria)"),
            "de-de" => array("de_DE", "German (Germany)"),
            "de-li" => array("de_DE", "German (Liechtenstein)"),
            "de-lu" => array("de_DE", "German (Luxembourg)"),
            "de-ch" => array("de_DE", "German (Switzerland)"),
            "it" => array("it_IT", "Italian (Standard)"),
            "it_IT" => array("it_IT", "Italian (Standard)"),
            "it-ch" => array("it_IT", "Italian (Switzerland)"),
            "no" => array("nb_NO", "Norwegian"),
            "nb_NO" => array("nb_NO", "Norwegian"),
            "nb" => array("nb_NO", "Norwegian (Bokmal)"),
            "nn" => array("nb_NO", "Norwegian (Nynorsk)"),
            "nl" => array("nl_NL", "Dutch"),
            "nl_NL" => array("nl_NL", "Dutch"),
            "pl" => array("pl_PL", "Polish"),
            "pl_PL" => array("pl_PL", "Polish"),
            "pt" => array("pt_PT", "Portuguese"),
            "pt_PT" => array("pt_PT", "Portuguese"),
            "pt-br" => array("pt_PT", "Portuguese"),
            "es" => array("es_ES", "Spanish"),
            "es_ES" => array("es_ES", "Spanish"),
            "es-ar" => array("es_ES", "Spanish (Argentina)"),
            "es-bo" => array("es_ES", "Spanish (Bolivia)"),
            "es-cl" => array("es_ES", "Spanish (Chile)"),
            "es-co" => array("es_ES", "Spanish (Colombia)"),
            "es-cr" => array("es_ES", "Spanish (Costa Rica)"),
            "es-do" => array("es_ES", "Spanish (Dominican Republic)"),
            "es-ec" => array("es_ES", "Spanish (Ecuador)"),
            "es-sv" => array("es_ES", "Spanish (El Salvador)"),
            "es-gt" => array("es_ES", "Spanish (Guatemala)"),
            "es-hn" => array("es_ES", "Spanish (Honduras)"),
            "es-mx" => array("es_ES", "Spanish (Mexico)"),
            "es-ni" => array("es_ES", "Spanish (Nicaragua)"),
            "es-pa" => array("es_ES", "Spanish (Panama)"),
            "es-py" => array("es_ES", "Spanish (Paraguay)"),
            "es-pe" => array("es_ES", "Spanish (Peru)"),
            "es-pr" => array("es_ES", "Spanish (Puerto Rico)"),
            "es-es" => array("es_ES", "Spanish (Spain)"),
            "es-uy" => array("es_ES", "Spanish (Uruguay)"),
            "es-ve" => array("es_ES", "Spanish (Venezuela)"),
            "sv" => array("sv_SE", "Swedish"),
            "sv_SE" => array("sv_SE", "Swedish"),
            "sv-fi" => array("sv_SE", "Swedish (Finland)"),           
            "sv-sv" => array("sv_SE", "Swedish (Sweden)"),
            "th" => array("th_TH", "Thai"),
            "th_TH" => array("th_TH", "Thai"),
            "tr" => array("tr_TR", "Turkish"),
            "tr_TR" => array("tr_TR", "Turkish"),
            "tr-tr" => array("tr_TR", "Turkish (Turkey)"),
            "tr-cy" => array("tr_TR", "Turkish (Cypern)"),
        );
        $this->availableLangs = array(
            'ar' => 'Arabic',
            'da' => 'Danish',
            'de' => 'German',
            'en' => 'English',
            'es' => 'Spanish',
            'fi' => 'Finnish',
            'fr' => 'French',
            'it' => 'Italien',
            'no' => 'Norwegian',
            'nl' => 'Dutch',
            'pl' => 'Polish',
            'pt' => 'Portuguese',
            'sv' => 'Swedish',
            'th' => 'Thai',
            'tr' => 'Turkish',
            'zh' => 'Chinese'
        );
        if (!empty($langID)) {
            $langMatch = $langID;
        } else {
            //Check if getting HTTP ACCEPT LANGUAGE from browser
            if (!empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                //Create array with user's browser's accepted languages
                $accepted = !empty($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? $this->parseLanguageList($_SERVER['HTTP_ACCEPT_LANGUAGE']) : "";
                //Create array with systems available languages
                $available["1.0"] = array_keys($this->availableBrowserLangs);
                //Match the two arrays and return the match with the highest priority
                $matches = $this->findMatches($accepted, $available);

                //Check if there is matches
                if (count($matches) == 0) {
                    $langMatch = "en_US";
                } else {
                    $langMatch = reset($matches)[0];
                }
            } else {
                $langMatch = "en_US";
            }
        }
        
        
        
        putenv("LC_ALL=" . $this->availableBrowserLangs[$langMatch][0]);
        putenv("LANG=" . $this->availableBrowserLangs[$langMatch][0]);
        putenv("LANGUAGE=");
        setlocale(LC_ALL, $this->availableBrowserLangs[$langMatch][0] . ".UTF-8");
        $this->currentLang = $this->availableBrowserLangs[$langMatch][0];

        //Domain is what the .po and .mo files is named and located
        $domain = 'messages';
        $absolutei18nPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . "../../.." . DIRECTORY_SEPARATOR . "i18n";

        bindtextdomain($domain, $absolutei18nPath);
        bind_textdomain_codeset($domain, 'UTF-8');
        textdomain($domain);
        $_SESSION['lang'] = $this->availableBrowserLangs[$langMatch][0];
    }

    function getCurrentLang() {
        return $this->currentLang;
    }

    function isRTL() {
        if ($this->currentLang == "ar") {
            return true;
        } else {
            return false;
        }
    }

    function getAvailableLangs() {
        return $this->availableLangs;
    }
    
    function replaceSystemName($string){
        $config = Config::getConfig();
        if(!empty($config['site']) && $config['site'] == "optomed"){
            $system = "Optomed Avenue";
        }else{
            $system = "RetinaLyze";
        }
        $systemReplacedString = str_replace("%system%", $system, $string);
        if(!empty($config['site']) && $config['site'] == "optomed"){
            $company = "Optomed Avenue";
        }else{
            $company = "RetinaLyze System A/S";
        }
        $companyReplacedString = str_replace("%company%", $company, $systemReplacedString);
        return $companyReplacedString;
    }

    // parse list of comma separated language tags and sort it by the quality value
    function parseLanguageList($languageList) {
        if (is_null($languageList)) {
            if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                return array();
            }
            $languageList = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
        }
        $languages = array();
        $languageRanges = explode(',', trim($languageList));
        foreach ($languageRanges as $languageRange) {
            if (preg_match('/(\*|[a-zA-Z0-9]{1,8}(?:-[a-zA-Z0-9]{1,8})*)(?:\s*;\s*q\s*=\s*(0(?:\.\d{0,3})|1(?:\.0{0,3})))?/', trim($languageRange), $match)) {
                if (!isset($match[2])) {
                    $match[2] = '1.0';
                } else {
                    $match[2] = (string) floatval($match[2]);
                }
                if (!isset($languages[$match[2]])) {
                    $languages[$match[2]] = array();
                }
                $languages[$match[2]][] = strtolower($match[1]);
            }
        }
        krsort($languages);
        return $languages;
    }

    // compare two parsed arrays of language tags and find the matches
    function findMatches($accepted, $available) {
        $matches = array();
        $any = false;
        foreach ($accepted as $acceptedQuality => $acceptedValues) {
            $acceptedQuality = floatval($acceptedQuality);
            if ($acceptedQuality === 0.0) {
                continue;
            }
            foreach ($available as $availableQuality => $availableValues) {
                $availableQuality = floatval($availableQuality);
                if ($availableQuality === 0.0) {
                    continue;
                }
                foreach ($acceptedValues as $acceptedValue) {
                    if ($acceptedValue === '*') {
                        $any = true;
                    }
                    foreach ($availableValues as $availableValue) {
                        $matchingGrade = $this->matchLanguage($acceptedValue, $availableValue);
                        if ($matchingGrade > 0) {
                            $q = (string) ($acceptedQuality * $availableQuality * $matchingGrade);
                            if (!isset($matches[$q])) {
                                $matches[$q] = array();
                            }
                            if (!in_array($availableValue, $matches[$q])) {
                                $matches[$q][] = $availableValue;
                            }
                        }
                    }
                }
            }
        }
        if (count($matches) === 0 && $any) {
            $matches = $available;
        }
        krsort($matches);
        return $matches;
    }

    // compare two language tags and distinguish the degree of matching
    function matchLanguage($a, $b) {
        $a = explode('-', $a);
        $b = explode('-', $b);
        for ($i = 0, $n = min(count($a), count($b)); $i < $n; $i++) {
            if ($a[$i] !== $b[$i]) {
                break;
            }
        }
        return $i === 0 ? 0 : (float) $i / count($a);
    }

}