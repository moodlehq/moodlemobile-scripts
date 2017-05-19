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
 * Script for renaming the strings using AMOS script
 */

// Check we are in CLI.
if (isset($_SERVER['REMOTE_ADDR'])) {
    exit(1);
}

define("MOODLE_INTERNAL", 1);

define("STRING_FILE_PATH", "/Users/juanleyvadelgado/Documents/MoodleMobile/moodle-local_moodlemobileapp/lang/en/local_moodlemobileapp.php");

// Load Moodle string file.
$string = array();
include(STRING_FILE_PATH);

$oldstrings = array();
$newstrings = array();

// Detect old and new strings
foreach ($string as $name => $value) {
    if (strpos($name, '.') > 0) {
        $newstrings[$name] = $value;
    } else {
        $oldstrings[$name] = $value;
    }
}

foreach ($newstrings as $name => $value) {
    list($type, $component, $id) = explode('.', $name);

    if (!empty($oldstrings[$id])) {
        echo "  CPY [$id,local_moodlemobileapp],[$name,local_moodlemobileapp]\n";
    }
}

// Exit 0 mean success.
exit(0);