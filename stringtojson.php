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

$file = $argv[1];

$string = array();
if (file_exists($file)) {
    define("MOODLE_INTERNAL", 1);
    include($file);
}

if (!empty($string)) {
    echo json_encode($string, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

// Exit 0 mean success.
exit(0);