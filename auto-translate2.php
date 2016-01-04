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
 * Script for translating strings based on the string Id
 * This script searfch for string Ids defined in the app language JSON files in the Moodle language file specified as parameter
 * NOTE: Translations for that strings Ids in AMOS overwrites this automatic translation (see fetch-langpacks.sh)
 */

// Check we are in CLI.
if (isset($_SERVER['REMOTE_ADDR'])) {
    exit(1);
}

define("MOODLE_INTERNAL", 1);

define("STRING_FILES_PATH", "/Users/juanleyvadelgado/Documents/MoodleMobile/moodle-langpacks/moodle-langpacks/");
define("JSON_FILES_PATH",   "/Users/juanleyvadelgado/Documents/MoodleMobile/moodlemobile2/www/build/lang/");
define("CORE_FILES_PATH",   "/Users/juanleyvadelgado/Documents/MoodleMobile/moodlemobile2/www/");

$moodlestringfiles = array('my.php', 'moodle.php', 'chat.php', 'completion.php', 'choice.php', 'badges.php', 'assign.php',
                            'feedback.php', 'repository_coursefiles.php', 'forum.php', 'survey.php', 'lti.php', 'enrol_self.php',
                            'search.php');
$numfound = 0;
$numnotfound = 0;
$notfound = array();

$files = scandir(JSON_FILES_PATH);
$languages = array();

foreach ($files as $f) {
    if (strpos($f, ".json")) {
        $languages[] = str_replace(".json", "", $f);
    }
}

$englishids = file_get_contents(JSON_FILES_PATH . "en.json");
$englishids = (array) json_decode($englishids);
$englishids = array_keys($englishids);

foreach ($moodlestringfiles as $stringfilename) {

    foreach ($languages as $lang) {
        if ($lang == "en") {
            continue;
        }

        $_lang = str_replace('-', '_', $lang);
        $stringfile = STRING_FILES_PATH . "$_lang/$stringfilename";
        if (!file_exists($stringfile)) {
            print("String file $stringfilename doesn't exists for language $lang (Path: $stringfile)\n");
            continue;
        }

        // Load Moodle string file.
        $string = array();
        include($stringfile);

        // Load app JSON file.
        $jsonfile = JSON_FILES_PATH . "$lang.json";
        $jsonstrings = file_get_contents($jsonfile);
        $jsonstrings = (array) json_decode($jsonstrings);

        // Missing strings.
        $found = false;
        foreach ($englishids as $id) {
            if (strpos($id, ".") === false) {
                continue;
            }
            list($type, $component, $plainid) = explode('.', $id);

            if (empty($jsonstrings[$id]) and !empty($string[$plainid])) {
                print("$id found -> " . $string[$plainid] . " \n");
                $jsonstrings[$id] = str_replace('{$a}', '{{$a}}',$string[$plainid]);
                // Prevent double.
                $jsonstrings[$id] = str_replace('{{{$a}}}', '{{$a}}',$jsonstrings[$id]);
                $found = true;
                $numfound++;
            } else if (empty($jsonstrings[$id])) {
                $notfound[$plainid][$lang] = "$stringfilename";
                $numnotfound++;
            }
        }

        if ($found) {
            // Order the array.
            ksort($jsonstrings);
            file_put_contents($jsonfile, str_replace('\/', '/', json_encode($jsonstrings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)));
        }

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

            $jsonstrings = file_get_contents($path);
            $jsonstrings = (array) json_decode($jsonstrings);

            $finalstrings = array_replace($jsonstrings, $strings);
            ksort($finalstrings);
            file_put_contents($path, str_replace('\/', '/', json_encode($finalstrings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)));
        }
    }
}

ksort($notfound);
foreach ($notfound as $key => $nf) {
    print("$key: ");
    print(implode(' ', array_keys($nf)));
    print("\n\n");
}
print("\n\nFound $numfound\n");
print("Not found $numnotfound\n\n");

// Exit 0 mean success.
exit(0);