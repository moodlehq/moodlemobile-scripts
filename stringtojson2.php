<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Script for converting a language string file in Moodle to a JSON language file for Moodle Mobile
 * This script is called by fetch-langpacks.sh passing as argument the file to convert
 */

// Check we are in CLI.
if (isset($_SERVER['REMOTE_ADDR'])) {
    exit(1);
}

define("JSON_FILES_PATH",   "/Users/juanleyvadelgado/Documents/MoodleMobile/moodlemobile2/www/build/lang/");
define("CORE_FILES_PATH",   "/Users/juanleyvadelgado/Documents/MoodleMobile/moodlemobile2/www/");


$lang = str_replace('_', '-', $argv[1]);
$file = $argv[2];

$string = array();
if (file_exists($file)) {
    // The language string file checks for this constant.
    define("MOODLE_INTERNAL", 1);
    include($file);
}

if (!empty($string)) {
    // Skip appstoredescription.
    unset($string['appstoredescription']);
    unset($string['pluginname']);

    $jsonfile = JSON_FILES_PATH . "$lang.json";
    $jsonstrings = file_get_contents($jsonfile);
    $jsonstrings = (array) json_decode($jsonstrings);

    // We overwrite existing translations that maybe were automatically created by auto-translate.php.
    foreach ($string as $id => $content) {
        // Omit old strings.
        if (strpos($id, ".") === false) {
            echo "lang $lang: omitting $id \n";
            continue;
        }
        $content = str_replace('{$a}', '{{$a}}', $content);
        // Prevent double.
        $content = str_replace('{{{$a}}}', '{{$a}}', $content);
        $jsonstrings[$id] = $content;
    }

    ksort($jsonstrings);
    file_put_contents($jsonfile, json_encode($jsonstrings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

    // Split the translation.
    $componenttranslations = array();
    foreach ($jsonstrings as $key => $value) {
        list($type, $component, $plainid) = explode('.', $key);
        $componenttranslations["$type.$component"][$plainid] = $value;
    }

    foreach ($componenttranslations as $key => $strings) {
        list($type, $component) = explode('.', $key);
        if ($type == 'mma') {
            $path = CORE_FILES_PATH . "addons/$component/lang/$lang.json";
        } else {
            switch ($component) {
                case 'core':
                    $path = CORE_FILES_PATH . "core/lang/$lang.json";
                    break;
                default:
                    $path = CORE_FILES_PATH . "core/components/$component/lang/$lang.json";
            }
        }
        file_put_contents($path, str_replace('\/', '/', json_encode($strings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)));
    }
}

// Exit 0 mean success.
exit(0);