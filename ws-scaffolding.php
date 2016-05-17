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
 * ws.json is a file containing the new Web Service information and settings, see ws-samples directory for examples
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
        if (!empty($data->default) or (isset($data->default) and ($data->default === 0 or $data->default === "0" or $data->default === false))) {
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

if (file_exists($externalfileold)) {
    $externalfile = $externalfileold;
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

// Use the survey file as template.
$testfile = "$data->moodlebasepath/mod/$pluginname/tests/external_test.php";
$testfileold = "$data->moodlebasepath/mod/$pluginname/tests/externallib_test.php";
if (!file_exists($testfile)  and !file_exists($testfileold)) {
    output("Creating new test file in: $testfile");
    $lines = file("$data->moodlebasepath/mod/survey/tests/externallib_test.php");
    $lines = implode("", array_splice($lines, 0, 69)) . "\n}\n";

    $lines = str_replace("survey", $pluginname, $lines);
    $lines = str_replace("Survey", ucfirst($pluginname), $lines);
    $lines = str_replace("Moodle 3.0", $data->since, $lines);
    $lines = str_replace("2015 Juan Leyva <juan@moodle.com>", $data->copyright, $lines);

    file_put_contents($testfile, $lines);
}

if (file_exists($testfileold)) {
    $testfile = $testfileold;
}



// Check bump versions.
define('MOODLE_INTERNAL', true);
define('MATURITY_ALPHA', true);
define('MATURITY_BETA', true);

if (!empty($data->bumpversion)) {

    require_once($data->moodlebasepath . "/version.php");
    $newversion = $version + 0.01;
    output("Moodle version bumped: from ". number_format($version, 2, '.', '') . " to $newversion");
    file_replace_contents($data->moodlebasepath . "/version.php", number_format($version, 2, '.', ''), $newversion);
}

if (!empty($data->bumpmodversion)) {
    $plugin = new stdClass();
    require_once($data->moodlebasepath . "/mod/$pluginname/version.php");
    $newversion = $plugin->version + 1;
    output("Plugin version bumped: from $plugin->version to $newversion");
    file_replace_contents($data->moodlebasepath . "/mod/$pluginname/version.php", "$plugin->version", "$newversion");
}

// Include new function into the mobile service.
if (!empty($data->addtothemobileservice)) {
    $serviceslib = $data->moodlebasepath . "/lib/db/services.php";

    if (empty($data->addafter)) {
        $data->addafter = "core_message_send_instant_messages";
    }

    file_replace_contents($serviceslib, "$data->addafter',", "$data->addafter',\n            '$data->name',");

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

file_replace_contents($servicesfile, ");", "\n" . $template . "\n);");

// Create the external functions.

$parametersdoc = "";
$parametersdec = "";
$parameterslist = "";
$parametersarr = "";

foreach ($data->parameters as $parameter => $pdata) {
    $parametersarr .= "\n            '$parameter' => \$$parameter,";

    if (!empty($parametersdec)) {
        $parametersdec .= ", ";
    }

    if (!empty($pdata->external_value)) {

        switch ($pdata->external_value->type) {
            case "PARAM_INT":
                $paramtype = 'int';
                break;
            case "PARAM_FLOAT":
                $paramtype = 'float';
                break;
            case "PARAM_BOOL":
                $paramtype = 'bool';
                break;
            default:
                $paramtype = 'string';
        }

        $parametersdoc .= "\n     * @param $paramtype \$${parameter} " . $pdata->external_value->description;
        $parametersdec .= "\$${parameter}";
        if ($pdata->external_value->default or
                (isset($pdata->external_value->default) and
                ($pdata->external_value->default === false or $pdata->external_value->default === 0 or
                $pdata->external_value->default === "0"))) {

            $parametersdec .= " = " . $pdata->external_value->default;
        }

        $parameterslist .= "\n                '$parameter' => " . print_external_value($pdata->external_value);

    } else if ($pdata->type == "external_multiple_structure") {
        $parametersdoc .= "\n     * @param array \$${parameter} $pdata->description";

        $parametersdec .= "\$${parameter}";
        if ($pdata->default) {
            $parametersdec .= " = array()";
        }

        $parameterslist .= "\n                '$parameter' => new external_multiple_structure(\n";

        if (!empty($pdata->external_value)) {
            $parameterslist .= '                    ' . print_external_value($pdata->external_value);
        } else if (!empty($pdata->external_single_structure)) {
            $parameterslist .= "                    new external_single_structure(
                        array(";

            foreach ($pdata->external_single_structure as $singlestructure) {
                // Read from XMLDB.
                if (!empty($singlestructure->name)) {
                    $parameterslist .= "\n                            '$singlestructure->name' => " . print_external_value($singlestructure);
                }
            }
            $parameterslist .= "\n                        )";
            $parameterslist .= "\n                    )";
        }

        if (!empty($pdata->description) or !empty($pdata->required) or !empty($pdata->default)) {
            $required = "";
            if (!empty($pdata->required)) {
                $required = ", $pdata->required";
                if (!empty($pdata->default)) {
                    $required .= ", $pdata->default";
                }
            }
            $parameterslist .= ",\n                    '$pdata->description'$required";
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
            array($parameterslist
            )
        );
    }
";


$functiontpl = "
    /**
     * $data->description
     * $parametersdoc
     * @return $data->returndescription
     * @since $data->since
     */
    public static function $function($parametersdec) {

        \$warnings = array();

        \$params = array( $parametersarr
        );
        \$params = self::validate_parameters(self::${function}_parameters(), \$params);

        \$result = array();
        \$result[''] = $;
        \$result['warnings'] = \$warnings;
        return \$result;
    }
";

$parameterslist = "";
foreach ($data->returns as $parameter => $pdata) {

    $ismultiple = !empty($pdata->type) and $pdata->type == "external_multiple_structure";
    $issingle = !empty($pdata->external_single_structure);

    if ($ismultiple or $issingle) {

        if ($ismultiple) {
            $parameterslist .= "\n                '$parameter' => new external_multiple_structure(";
        } else if ($issingle) {
            $parameterslist .= "\n                '$parameter' => new external_single_structure(
                        array(";
        }

        if (!empty($pdata->external_value)) {
            $parameterslist .= "\n                    " . print_external_value($pdata->external_value);

            if (!empty($pdata->description) or !empty($pdata->required) or !empty($pdata->default)) {
                $required = "";
                if (!empty($pdata->required)) {
                    $required = ", $pdata->required";
                    if (!empty($pdata->default)) {
                        $required .= ", $pdata->default";
                    }
                }
                $parameterslist .= "\n                    ' $pdata->description'$required),\n";
            }

        } else if (!empty($pdata->external_single_structure)) {
            if ($ismultiple) {
                $parameterslist .= "                    new external_single_structure(
                        array(";
            }
            foreach ($pdata->external_single_structure as $singlestructure) {
                // Read from XMLDB.
                if (!empty($singlestructure->from) && $singlestructure->from == 'xmldb') {
                    $file = $data->moodlebasepath . "/". $singlestructure->file;
                    $xmldb = simplexml_load_file($file);

                    // Extract fields.
                    $tabledata = array();
                    foreach ($xmldb->TABLES->TABLE as $table) {
                        foreach ($table->attributes() as $key => $val) {
                            if ($key == "NAME" and $val == $singlestructure->table) {
                                foreach ($table->FIELDS->FIELD as $field) {
                                    $fielddata = array();
                                    foreach ($field->attributes() as $key => $val) {
                                        $fielddata[strtolower($key)] = (string) $val;
                                    }
                                    $name = $fielddata['name'];
                                    $tabledata[$name] = $fielddata;
                                }
                                break 2;
                            }
                        }
                    }

                    // Now we have all the fields.
                    foreach ($tabledata as $fieldname => $fielddata) {
                        $externaldata = new stdClass;
                        $externaldata->type = ($fielddata['type'] == 'int') ? 'PARAM_INT' : 'PARAM_RAW';
                        $externaldata->description = (!empty($fielddata['comment'])) ? str_replace("'", "\'", $fielddata['comment']) : '';
                        $externaldata->required = 'VALUE_OPTIONAL';
                        $parameterslist .= "\n                            '$fieldname' => " . print_external_value($externaldata);
                    }
                }
                if (!empty($singlestructure->name)) {
                    $parameterslist .= "\n                            '$singlestructure->name' => " . print_external_value($singlestructure);
                }
            }

            $parameterslist .= "\n                        )";
            if (!empty($pdata->description) or !empty($pdata->required) or !empty($pdata->default)) {
                $required = "";
                if (!empty($pdata->required)) {
                    $required = ", $pdata->required";
                    if (!empty($pdata->default)) {
                        $required .= ", $pdata->default";
                    }
                }
                $parameterslist .= ", ' $pdata->description'$required";
            }
            $parameterslist .= "\n                ),";
        }
    } else if (!empty($pdata->external_value)) {

        $parameterslist .= "\n                '$parameter' => " . print_external_value($pdata->external_value);

    }
}


$returnstpl = "
    /**
     * Describes the $function return value.
     *
     * @return external_single_structure
     * @since $data->since
     */
    public static function ${function}_returns() {
        return new external_single_structure(
            array($parameterslist
                'warnings' => new external_warnings(),
            )
        );
    }
";

file_replace_contents($externalfile, "\n}\n", $parameterstpl . $functiontpl . $returnstpl . "\n}\n");

if (!empty($data->testtemplate)) {
    list ($file, $function) = explode(":", $data->testtemplate);

    $test = file_get_contents("$data->moodlebasepath/$file");
    $teststart = strpos($test, "    public function $function");

    if (!empty($teststart)) {
        $test = substr($test, $teststart);
        preg_match('/\n    }\n/', $test, $matches, PREG_OFFSET_CAPTURE);
        $endfunction = $matches[0][1];

        if (!empty($endfunction)) {
            $test = substr($test, 0, $endfunction + 7);
            $function = str_replace("survey", $pluginname, $function);
            $test = str_replace("survey", $pluginname, $test);
            $test = str_replace("Survey", ucfirst($pluginname), $test);

            $testheader = "
    /**
     * Test $function
     */
";

            file_replace_contents($testfile, "\n}\n", $testheader . $test . "\n}\n");
            output("Tempalte test function added");
        }
    }
}

if (!empty($data->basictest)) {

    $component = "${type}_${pluginname}";

    $basictest = "
    /**
     * Test $function
     */
    public function test_${function}() {

        \$result = ${component}_external::$function();
        \$result = external_api::clean_returnvalue(${component}_external::${function}_returns(), \$result);

        try {
            ${component}_external::${function}();
            \$this->fail('Exception expected due to missing capability.');
        } catch (required_capability_exception \$e) {
            \$this->assertEquals('nopermissions', \$e->errorcode);
        }

    }
";

    file_replace_contents($testfile, "\n}\n", $basictest . "\n}\n");
}

// Exit 0 mean success.
exit(0);
