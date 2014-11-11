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
 * This script search for string Ids defined in the app language JSON files in the Moodle language file specified as parameter
 * NOTE: Translations for that strings Ids in AMOS overwrites this automatic translation (see fetch-langpacks.sh)
 */

// Check we are in CLI.
if (isset($_SERVER['REMOTE_ADDR'])) {
    exit(1);
}

define("MOODLE_INTERNAL", 1);

define("STRING_FILES_PATH", "/Users/juanleyvadelgado/Documents/MoodleMobile/moodle-langpacks/moodle-langpacks/");
define("JSON_FILES_PATH",   "/Users/juanleyvadelgado/Documents/MoodleMobile/GIT/lang/");

if (count($argv) != 2) {
    print("Missing parameter string file name: (grades.php, moodle.php, ..)\n");
    exit(1);
}

$stringfilename = $argv[1];

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

foreach ($languages as $lang) {
    if ($lang == "en") {
        continue;
    }

    $stringfile = STRING_FILES_PATH . "$lang/$stringfilename";
    if (!file_exists($stringfile)) {
        print("String file $stringfilename doesn't exists for language $lang (Path: $stringfile)\n");
        continue;
    }

    print("=========================\n");
    print("$lang\n");
    print("=========================\n");

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
        if (empty($jsonstrings[$id]) and !empty($string[$id])) {
            print("$id found -> " . $string[$id] . " \n");
            $jsonstrings[$id] = $string[$id];
            $found = true;
        } else if (empty($jsonstrings[$id])) {
            print("NOT $id found \n");
        }
    }

    if ($found) {
        // Order the array.
        ksort($jsonstrings);
        file_put_contents($jsonfile, json_encode($jsonstrings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
    print("=========================\n");
}

// Exit 0 mean success.
exit(0);