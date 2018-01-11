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
 * Script for detecting english core strings changes.
 */

// Check we are in CLI.
if (isset($_SERVER['REMOTE_ADDR'])) {
    exit(1);
}

define("MOODLE_INTERNAL", 1);

define("STRING_FILES_PATH", "/Users/juanleyvadelgado/Documents/MoodleMobile/moodle-langpacks/moodle-langpacks/");
define("BRANCH", "MOODLE_34_STABLE");
define("JSON_FILES_PATH",   "/Users/juanleyvadelgado/Documents/MoodleMobile/moodlemobile2/www/build/lang/");

function convert_string($string) {
    $string = str_replace('$a->', '$a.', $string);
    $string = str_replace('{$a', '{{$a', $string);
    $string = str_replace('}', '}}', $string);
    // Prevent double.
    $string = str_replace(array('{{{', '}}}'), array('{{', '}}'), $string);
    return trim($string);
}

exec("cd ".STRING_FILES_PATH."; git checkout ".BRANCH."; git pull");

$moodlestringfiles = array('my.php', 'moodle.php', 'error.php', 'repository.php', 'chat.php', 'completion.php', 'choice.php', 'badges.php', 'assign.php',
                            'feedback.php', 'lesson.php', 'data.php', 'repository_coursefiles.php', 'forum.php', 'survey.php', 'lti.php', 'enrol_self.php',
                            'search.php', 'scorm.php', 'message.php', 'wiki.php', 'quiz.php', 'grades.php', 'grading.php',
                            'assignsubmission_onlinetext', 'assignsubmission_file.php', 'assignsubmission_comments.php',
                            'assignfeedback_comments.php', 'assignfeedback_editpdf.php', 'assignfeedback_file.php', 'assignfeedback_offline.php',
                            'question.php',
                            'quizaccess_delaybetweenattempts.php', 'quizaccess_ipaddress.php', 'quizaccess_numattempts.php',
                            'quizaccess_openclosedate.php', 'quizaccess_password.php', 'quizaccess_safebrowser.php',
                            'quizaccess_securewindow.php', 'quizaccess_timelimit.php',
                            'compentency.php', 'tool_lp.php', 'auth.php', 'langconfig.php', 'enrol_guest.php'
                            );

$englishids = file_get_contents(JSON_FILES_PATH . "en.json");
$englishids = (array) json_decode($englishids);
$englishids = array_keys($englishids);
sort($englishids);

$lang = 'en';
// Load app JSON file.
$jsonfile = JSON_FILES_PATH . "$lang.json";
$jsonstrings = file_get_contents($jsonfile);
$jsonstrings = (array) json_decode($jsonstrings);

foreach ($moodlestringfiles as $stringfilename) {

    // Load Moodle string file.
    $string = array();

    $stringf = STRING_FILES_PATH . 'en/' . $stringfilename;
    if (!file_exists($stringf)) {
        echo "Missing string file $stringf";
        continue;
    }
    include($stringf);

    // Iterate over the app string ids.
    foreach ($englishids as $id) {
        if (strpos($id, ".") === false) {
            continue;
        }
        $isplugin = false;

        list($type, $component, $plainid) = explode('.', $id);

        if (empty($string[$plainid]) or empty($jsonstrings[$id])) {
            continue;
        }

        $string[$plainid] = str_replace(array("\n", "\t", "\r\n"), '', $string[$plainid]);
        $jsonstrings[$id] = str_replace(array("\n", "\t", "\r\n"), '', $jsonstrings[$id]);

        // We are translating and addon that is a Moodle module.
        if (strpos($component, 'mod_') !== false) {
            $isplugin = true;
            list($mod, $modname) = explode('_', $component);
            if ($modname == str_replace('.php', '', $stringfilename) && !empty($jsonstrings[$id]) && !empty($string[$plainid])) {
                if ($jsonstrings[$id] != convert_string($string[$plainid])) {
                    echo "\n$stringfilename ($modname): String $id has changed from:\n  " . $jsonstrings[$id] . " to \n  " . convert_string($string[$plainid]) . "\n";
                }
                continue;
            }
        }

        if (!$isplugin && $jsonstrings[$id] != convert_string($string[$plainid])) {
            $changes[$id] = [];
            $changes[$id][] = "\n$stringfilename ($modname): String $id has changed from:\n  " . $jsonstrings[$id] . " to \n  " . convert_string($string[$plainid]) . "\n";
        }
    }
}

print_r($changes);

// Exit 0 mean success.
exit(0);