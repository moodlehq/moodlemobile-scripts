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
 * Script for ordering/fixing the Web Services API documentation.
 */

// Check we are in CLI.
if (isset($_SERVER['REMOTE_ADDR'])) {
    exit(1);
}

define("CLI_SCRIPT", 1);
define("MOODLE_INTERNAL", 1);

define("MOODLE_LATEST_VERSION", "/Users/juanleyvadelgado/wwwdata/m/stable_master/moodle");
define("DOCUMENTATION_PATH", "doc.txt");

require_once(MOODLE_LATEST_VERSION . '/config.php');

$doc = file_get_contents(DOCUMENTATION_PATH);

$doc = str_replace(array("\n", "\r"), '', $doc);
$lines = explode("|-", $doc);

$functions = array();
foreach ($lines as $line) {
    $els = explode("|", $line);

    $fname = trim($els[3]);
    $fname = str_replace('()', '', $fname);
    $oldfname = $els[6];
    $version = $els[9];
    $description = $els[12];
    $issue = $els[14];

    $fnameels = explode('_', $fname);
    $component = $fnameels[0] . '_' . $fnameels[1];

    $functions[$fname] = array(
        'fname' => $fname,
        'oldfname' => $oldfname,
        'version' => $version,
        'description' => $description,
        'issue' => $issue,
        'issue' => $issue,
        'component' => $component,
    );
}

$orderedfunctions = array_keys($functions);
sort($orderedfunctions);

$latestfunctions = get_external_functions(MOODLE_LATEST_VERSION);

echo '
{| class="wikitable sortable"
!Area!! Name !! Introduced in !! class="unsortable" |Description !!  Available AJAX !! Login required !! Services ';

foreach ($orderedfunctions as $fname) {

    $oldfname = $functions[$fname]['oldfname'];
    $version = $functions[$fname]['version'];
    $description = $functions[$fname]['description'];
    $issue = $functions[$fname]['issue'];
    $component = $functions[$fname]['component'];
    $ajax = (!empty($latestfunctions[$fname]['ajax'])) ? 'Yes' : 'No';
    $loginrequired = (!isset($latestfunctions[$fname]['loginrequired']) || $latestfunctions[$fname]['loginrequired']) ? 'Yes' : 'No';
    $services = (!empty($latestfunctions[$fname]['services'])) ? implode(',', $latestfunctions[$fname]['services']) : '';

    echo '
|-
| ' . $component . ' || ' . $fname . ' || ' . $version . ' || ' . $description . ' || ' . $ajax . ' || ' . $loginrequired. ' || ' . $services;

}
echo '
|-
|}
';


echo "\n\n\n MISSING DETECTED FUNCTIONS HERE \n\n\n";

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

$newfunctions = array_diff(array_keys($latestfunctions), $orderedfunctions);
sort($newfunctions);

foreach ($newfunctions as $fname) {
    $classname = str_replace(array('_external', '\external'), '', $latestfunctions[$fname]['classname']);
    $description = addslashes($latestfunctions[$fname]['description']);
echo '
|-
| ' . $classname . '
| style="background:#D4FFDF;" | ' . $fname . ' || style="background:#D4FFDF;" | || style="background:#D4FFDF;" | 3.x || style="background:#D4FFDF;" | ' . $description . ' ||';
}
