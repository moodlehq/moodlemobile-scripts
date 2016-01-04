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
 * Script for creating the basic skeleton of a new Moodle Web Service.
 * How to use:
 *  php ws-scaffolding.php ws.json
 *
 * ws.json is a file containing the new Web Service information and settings, see ws-dist.json as an example
 */

// Check we are in CLI.
if (isset($_SERVER['REMOTE_ADDR'])) {
    print("Only CLI supported");
    exit(1);
}

if (empty($argv[1])) {
    print("Missing parameters, usage: php ws-scaffoldng.php ws.json");
    exit(1);
}

// Utility functions.

function output($line) {
    echo "  $line\n";
}

function file_replace_contents($file, $oldcontent, $newcontent) {
    file_put_contents($file, str_replace($oldcontent, $newcontent, file_get_contents($file)));
}

function print_external_value($data) {
    $s = "new external_value($data->type, '$data->description'";

    if (!empty($data->required)) {
        $s .= ", $data->required";
        if (!empty($data->default)) {
            $s .= ", $data->default";
        }
    }

    $s .= "),";
    return $s;
}

$file = $argv[1];

if (!file_exists($file)) {
    output("The specified file does not exist");
    exit(1);
}

$data = json_decode(file_get_contents($file));
if (!$data) {
    output("Invalid JSON data (parsing error)");
    exit(1);
}

if (!file_exists($data->moodlebasepath)) {
    output("We cannot find the Moodle installation");
    exit(1);
}

list($type, $pluginname, $function) = explode("_", $data->name, 3);

if ($type !== "mod") {
    output("Unsupported type of component");
    exit(1);
}

// Check if we need to create template files:
$externalfile = "$data->moodlebasepath/mod/$pluginname/classes/external.php";
$externalfileold = "$data->moodlebasepath/mod/$pluginname/externallib.php";

// Use the survey file as template.
if (!file_exists($externalfile) and !file_exists($externalfileold)) {
    output("Creating new external file in: $externalfile");
    $lines = file("$data->moodlebasepath/mod/survey/classes/external.php");
    $lines = implode("", array_splice($lines, 0, 41)) . "\n}\n";

    $lines = str_replace("survey", $pluginname, $lines);
    $lines = str_replace("Survey", ucfirst($pluginname), $lines);
    $lines = str_replace("Moodle 3.0", $data->since, $lines);
    $lines = str_replace("2015 Juan Leyva <juan@moodle.com>", $data->copyright, $lines);

    file_put_contents($externalfile, $lines);
}

$servicesfile = "$data->moodlebasepath/mod/$pluginname/db/services.php";
if (!file_exists($servicesfile)) {
    output("Creating new services file in: $servicesfile");
    $lines = file("$data->moodlebasepath/mod/survey/db/services.php");
    $lines = implode("", array_splice($lines, 0, 29)) . "\n);\n";

    $lines = str_replace("survey", $pluginname, $lines);
    $lines = str_replace("Survey", ucfirst($pluginname), $lines);
    $lines = str_replace("Moodle 3.0", $data->since, $lines);
    $lines = str_replace("2015 Juan Leyva <juan@moodle.com>", $data->copyright, $lines);

    file_put_contents($servicesfile, $lines);
}

if (file_exists($externalfileold)) {
    $externalfile = $externalfileold;
}

// Check bump versions.
if (!empty($data->bumpversion)) {
    define('MOODLE_INTERNAL', true);
    define('MATURITY_ALPHA', true);
    define('MATURITY_BETA', true);

    require_once($data->moodlebasepath . "/version.php");
    $newversion = $version + 0.01;
    output("Moodle version bumped: from ". number_format($version, 2, '.', '') . " to $newversion");
    file_replace_contents($data->moodlebasepath . "/version.php", number_format($version, 2, '.', ''), $newversion);

    $plugin = new stdClass();
    require_once($data->moodlebasepath . "/mod/$pluginname/version.php");
    $newversion = $plugin->version + 1;
    output("Plugin version bumped: from $plugin->version to $newversion");
    file_replace_contents($data->moodlebasepath . "/mod/$pluginname/version.php", "$plugin->version", "$newversion");
}

// Include new function into the mobile service.
if (!empty($data->addtothemobileservice)) {
    $serviceslib = $data->moodlebasepath . "/lib/db/services.php";
    file_replace_contents($serviceslib, "mod_imscp_get_imscps_by_courses',", "mod_imscp_get_imscps_by_courses',\n            '$data->name',");
    output("Function added to the mobile service");
}

// Service definition.
$template = "    'mod_${pluginname}_${function}' => array(
        'classname'     => 'mod_${pluginname}_external',
        'methodname'    => '$function',
        'description'   => '$data->description',
        'type'          => '$data->type',
        'capabilities'  => '$data->capabilities'
    ),";

file_replace_contents($servicesfile, ");", $template . "\n);");

// Create the external functions.

$parametersdoc = "";
$parametersdec = "";
$parameterslist = "";
$parametersarr = "";

foreach ($data->parameters as $parameter => $pdata) {
    $parametersarr = "\n            '$parameter' => \$$parameter,";
    if ($pdata->type == "external_multiple_structure") {
        $parametersdoc .= "\n     * @param array \$${parameter} $pdata->description";

        if (!empty($parametersdec)) {
            $parametersdec .= " ,";
        }

        $parametersdec .= "\$${parameter}";
        if ($pdata->default) {
            $parametersdec .= " = array()";
        }

        $parameterslist = "'$parameter' => new external_multiple_structure(\n";

        if (!empty($pdata->external_value)) {
            $parameterslist .= '                    ' . print_external_value($pdata->external_value);
        }

        if (!empty($pdata->description) or !empty($pdata->required) or !empty($pdata->default)) {
            $required = "";
            if (!empty($pdata->required)) {
                $required = ", $pdata->required";
                if (!empty($pdata->default)) {
                    $required .= ", $pdata->default";
                }
            }
            $parameterslist .= "\n                    '$pdata->description'$required";
        }
        $parameterslist .= "\n                ),";
    }
}

$parameterstpl = "
    /**
     * Describes the parameters for $function.
     *
     * @return external_external_function_parameters
     * @since $data->since
     */
    public static function {$function}_parameters() {
        return new external_function_parameters (
            array(
                $parameterslist
            )
        );
    }
";


$functiontpl = "
    /**
     * $data->description
     * $parametersdoc
     * @return array of surveys details
     * @since $data->since
     */
    public static function $function($parametersdec) {

        \$warnings = array();

        \$params = { $parametersarr
        };
        \$params = self::validate_parameters(self::${function}_parameters(), \$params);

        \$result = array();
        \$result[''] = $;
        \$result['warnings'] = \$warnings;
        return \$result;
    }
";


$returnstpl = "";

file_replace_contents($externalfile, "\n}\n", $parameterstpl . $functiontpl . $returnstpl . "\n}\n");


// Exit 0 mean success.
exit(0);
