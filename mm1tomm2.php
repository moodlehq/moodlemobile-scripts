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
 * Script for moving translations from mm1 to mm2
 */

// Check we are in CLI.
if (isset($_SERVER['REMOTE_ADDR'])) {
    exit(1);
}

function convert($basepath) {
    global $languages, $missingstrings;

    $mm2strings = (array) json_decode(file_get_contents($basepath . 'en.json'));

    foreach ($languages as $langcode => $mm1strings) {
        if ($langcode == "en") {
            continue;
        }
        $newstrings = array();

        foreach ($mm2strings as $key => $value) {
            if (!empty($mm1strings[$key])) {
                $newstrings[$key] = $mm1strings[$key];
                unset($missingstrings[$key]);
            } else {
                $missingstrings[$key] = $value;
            }
        }
        ksort($newstrings);

        $newfile = $basepath . $langcode . '.json';
        print("File $newfile with " . count($newstrings) . "strings\n");
        file_put_contents($newfile, json_encode($newstrings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    }
}

define("MM1_PATH", "/Users/juanleyvadelgado/Documents/MoodleMobile/GIT/");
define("MM2_PATH",   "/Users/juanleyvadelgado/Documents/MoodleMobile/moodlemobile2/www/");

$files = scandir(MM1_PATH . 'lang/');
$languages = array();
$missingstrings = array();

foreach ($files as $f) {
    if (strpos($f, ".json")) {
        $lang = str_replace(".json", "", $f);
        $languages[$lang] = (array) json_decode(file_get_contents(MM1_PATH . 'lang/' . $lang . '.json'));
    }
}

// MM2.
$basepath = MM2_PATH . 'core/lang/';
convert($basepath);

$base = MM2_PATH . 'addons/';
$dirs = scandir($base);
foreach ($dirs as $dir) {
    $langdir = $base . "$dir/lang/";
    if (file_exists($langdir)) {
        convert($langdir);
    }
}

$base = MM2_PATH . 'core/components/';
$dirs = scandir($base);
foreach ($dirs as $dir) {
    $langdir = $base . "$dir/lang/";
    if (file_exists($langdir)) {
        convert($langdir);
    }
}

ksort($missingstrings);
print(json_encode($missingstrings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
print("\n\nTOTAL missing: " . count($missingstrings) .  " \n\n");

// Exit 0 mean success.
exit(0);