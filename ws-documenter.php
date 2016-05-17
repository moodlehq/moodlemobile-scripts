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
 * Script for documenting in Wiki format new Web Services added in MOODLE_LATEST_VERSION.
 */

// Check we are in CLI.
if (isset($_SERVER['REMOTE_ADDR'])) {
    exit(1);
}

define("CLI_SCRIPT", 1);
define("MOODLE_INTERNAL", 1);

define("MOODLE_PREVIOUS_VERSION", "/Users/juanleyvadelgado/wwwdata/m/stable_30/moodle");
define("MOODLE_LATEST_VERSION", "/Users/juanleyvadelgado/wwwdata/m/stable_master/moodle");
define("MOODLE_LATEST_VERSION_NUMBER", "3.1");

if (!file_exists(MOODLE_LATEST_VERSION)) {
    exit("Invalid path" . MOODLE_LATEST_VERSION);
}

require_once(MOODLE_LATEST_VERSION . '/config.php');
require_once($CFG->libdir . '/adminlib.php');


// Function to return all the external functions in db/services.php.
function get_external_functions($path) {
    $functions = array();

    $coreservices = $path . '/lib/db/services.php';
    if (!file_exists($coreservices)) {
        exit("Invalid path $path");
    }
    require_once($coreservices);
    $externalfunctions = $functions;

    $plugintypes = core_component::get_plugin_types();

    foreach ($plugintypes as $plugintype => $unused) {
        // We need to include files here.
        $pluginswithfile = core_component::get_plugin_list_with_file($plugintype, 'db' . DIRECTORY_SEPARATOR . 'services.php');
        foreach ($pluginswithfile as $plugin => $notused) {
            $pluginpath = core_component::get_plugin_directory($plugintype, $plugin);

            // Normalize path.
            $pluginpath = str_replace(MOODLE_LATEST_VERSION, $path, $pluginpath);
            // Check if path exists.
            $servicespath = "$pluginpath/db/services.php";
            if (!file_exists($servicespath)) {
                continue;
            }
            require_once($servicespath);
            $externalfunctions = array_merge($externalfunctions, $functions);
        }
    }
    return $externalfunctions;
}

$previousfunctions = get_external_functions(MOODLE_PREVIOUS_VERSION);
$latestfunctions = get_external_functions(MOODLE_LATEST_VERSION);

// Calculate new functions.
$newfunctions = array_diff(array_keys($latestfunctions), array_keys($previousfunctions));
sort($newfunctions);

foreach ($newfunctions as $fname) {
    $classname = str_replace(array('_external', '\external'), '', $latestfunctions[$fname]['classname']);
    $description = addslashes($latestfunctions[$fname]['description']);
echo '
| -
| ' . $classname . '
| style="background:#D4FFDF;" | ' . $fname . ' || style="background:#D4FFDF;" | || style="background:#D4FFDF;" | ' . MOODLE_LATEST_VERSION_NUMBER . ' || style="background:#D4FFDF;" | ' . $description . ' ||';
}

// Exit 0 mean success.
exit(0);