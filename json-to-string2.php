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
 * Script for moving strings from a .json file to a Moodle .php string file.
 * This script ommit strings that are already translated in Moodle.
 */

// Check we are in CLI.
if (isset($_SERVER['REMOTE_ADDR'])) {
    exit(1);
}

define("MOODLE_INTERNAL", 1);

define("JSON_FILE_PATH",   "/Users/juanleyvadelgado/Documents/MoodleMobile/moodlemobile2/www/build/lang/en.json");
define("STRING_FILES_PATH", "/Users/juanleyvadelgado/Documents/MoodleMobile/moodle-local_moodlemobileapp/lang/en/local_moodlemobileapp.php");
define("MOODLE_STRING_FILES_PATH", "/Users/juanleyvadelgado/www/m/stable_master");

$jsonstrings = (array) json_decode(file_get_contents(JSON_FILE_PATH), true);

include(STRING_FILES_PATH);

$finalstrings = array_replace($string, $jsonstrings);
// Order the array.
ksort($finalstrings);

$templatefile = "<?php
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
 * Version details.
 *
 * @package    local
 * @subpackage moodlemobileapp
 * @copyright  2014 Juan Leyva <juanleyvadelgado@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

";

function compare_strings($string, $id, $value) {
    if (isset($string[$id])) {

        $cleanstring = str_replace(array('{', '}'), '', $string[$id]);

        $cleanvalue  = str_replace('$a.', '$a->', $value);
        $cleanvalue  = str_replace(array('{', '}'), '', $cleanvalue);

        return $cleanstring == $cleanvalue;
    }
    return false;
}

// Special strings that should be automatically translated.
$specialstrings = array(
    'mm.core.seemoredetail',
    'mm.core.timesup',
    'mm.course.overriddennotice',
    'mm.core.send'
);

foreach ($finalstrings as $key => $value) {
    if (strpos($key, 'mm.core.country') !== false) {
        continue;
    }

    // Strings that we'll be autotranslated but that are special cases.
    if (in_array($key, $specialstrings)) {
        continue;
    }

    // Ommit modules already translated files.
    if (strpos($key, 'mma.mod_quiz') !== false) {
        list($comp, $mod, $id) = explode(".", $key);
        list($t, $modname) = explode("_", $mod);
        $checkin = array(   "/mod/quiz/lang/en/quiz.php",
                            "/mod/quiz/accessrule/delaybetweenattempts/lang/en/quizaccess_delaybetweenattempts.php",
                            "/mod/quiz/accessrule/ipaddress/lang/en/quizaccess_ipaddress.php",
                            "/mod/quiz/accessrule/numattempts/lang/en/quizaccess_numattempts.php",
                            "/mod/quiz/accessrule/openclosedate/lang/en/quizaccess_openclosedate.php",
                            "/mod/quiz/accessrule/password/lang/en/quizaccess_password.php",
                            "/mod/quiz/accessrule/safebrowser/lang/en/quizaccess_safebrowser.php",
                            "/mod/quiz/accessrule/securewindow/lang/en/quizaccess_securewindow.php",
                            "/mod/quiz/accessrule/timelimit/lang/en/quizaccess_timelimit.php",
                            );

        foreach ($checkin as $langfile) {
            $string = array();
            include(MOODLE_STRING_FILES_PATH . $langfile);
            if (compare_strings($string, $id, $value)) {
                echo "$modname string with id $key exists \n";
                continue 2;
            }
        }
    } else if (strpos($key, 'mma.mod_') !== false) {
        list($comp, $mod, $id) = explode(".", $key);
        list($t, $modname) = explode("_", $mod);
        $string = array();
        include(MOODLE_STRING_FILES_PATH . "/mod/" . $modname . "/lang/en/" . $modname . ".php");
        if (compare_strings($string, $id, $value)) {
            echo "$modname string with id $key exists \n";
            continue;
        }
    }

    if (strpos($key, 'mm.core.') !== false) {
        list($comp, $mod, $id) = explode(".", $key);
        $string = array();
        include(MOODLE_STRING_FILES_PATH . "/lang/en/moodle.php");
        if (compare_strings($string, $id, $value)) {
            echo "string with id $key exists \n";
            continue;
        }
    }

    if (strpos($key, 'mma.competency') !== false) {
        list($comp, $mod, $id) = explode(".", $key);
        $checkin = array(   "/lang/en/competency.php",
                            "/admin/tool/lp/lang/en/tool_lp.php",
                            );

        foreach ($checkin as $langfile) {
            $string = array();
            include(MOODLE_STRING_FILES_PATH . $langfile);
            if (compare_strings($string, $id, $value)) {
                echo "string with id $key exists \n";
                continue 2;
            }
        }
    }

    if (strpos($key, 'mma.messages.') !== false) {
        list($comp, $mod, $id) = explode(".", $key);
        $string = array();
        include(MOODLE_STRING_FILES_PATH . "/lang/en/message.php");
        if (compare_strings($string, $id, $value)) {
            echo "string with id $key exists \n";
            continue;
        }
    }

    if (strpos($key, 'mm.question.') !== false) {
        list($comp, $mod, $id) = explode(".", $key);

        $checkin = array("/lang/en/question.php", "/question/behaviour/adaptive/lang/en/qbehaviour_adaptive.php",
                            "/question/behaviour/adaptivenopenalty/lang/en/qbehaviour_adaptivenopenalty.php",
                            "/question/behaviour/deferredcbm/lang/en/qbehaviour_deferredcbm.php",
                            "/question/behaviour/deferredfeedback/lang/en/qbehaviour_deferredfeedback.php",
                            "/question/behaviour/immediatecbm/lang/en/qbehaviour_immediatecbm.php",
                            "/question/behaviour/immediatefeedback/lang/en/qbehaviour_immediatefeedback.php",
                            "/question/behaviour/informationitem/lang/en/qbehaviour_informationitem.php",
                            "/question/behaviour/interactive/lang/en/qbehaviour_interactive.php",
                            "/question/behaviour/interactivecountback/lang/en/qbehaviour_interactivecountback.php",
                            "/question/behaviour/manualgraded/lang/en/qbehaviour_manualgraded.php"
                            );

        foreach ($checkin as $langfile) {
            $string = array();
            include(MOODLE_STRING_FILES_PATH . $langfile);
            if (compare_strings($string, $id, $value)) {
                echo "string with id $key exists \n";
                continue 2;
            }
        }
    }

    $value = str_replace("'", "\'", $value);
    $templatefile .= '$string' . "['$key'] = '$value';\n";
}
$templatefile .= "\n";

file_put_contents(STRING_FILES_PATH, $templatefile);

// Exit 0 mean success.
exit(0);