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
 * This script searfch for string Ids defined in the app language JSON files in the Moodle language file specified as parameter
 * NOTE: Translations for that strings Ids in AMOS overwrites this automatic translation (see fetch-langpacks.sh)
 */

// Check we are in CLI.
if (isset($_SERVER['REMOTE_ADDR'])) {
    exit(1);
}

define("MOODLE_INTERNAL", 1);

define("STRING_FILES_PATH", "/Users/juanleyvadelgado/Documents/MoodleMobile/moodle-langpacks/moodle-langpacks/");
define("BRANCH", "MOODLE_34_STABLE");
define("JSON_FILES_PATH",   "/Users/juanleyvadelgado/Documents/MoodleMobile/moodlemobile2/www/build/lang/");
define("CORE_FILES_PATH",   "/Users/juanleyvadelgado/Documents/MoodleMobile/moodlemobile2/www/");

exec("cd ".STRING_FILES_PATH."; git checkout ".BRANCH."; git pull");

$moodlestringfiles = array('my.php', 'moodle.php', 'error.php', 'repository.php', 'chat.php', 'completion.php', 'choice.php', 'badges.php', 'assign.php',
                            'feedback.php', 'lesson.php', 'data.php', 'repository_coursefiles.php', 'forum.php', 'survey.php', 'lti.php', 'enrol_self.php',
                            'search.php', 'scorm.php', 'message.php', 'wiki.php', 'quiz.php', 'grades.php', 'grading.php',
                            'assignsubmission_onlinetext', 'assignsubmission_file.php', 'assignsubmission_comments.php',
                            'assignfeedback_comments.php', 'assignfeedback_editpdf.php', 'assignfeedback_file.php', 'assignfeedback_offline.php',
                            'question.php', 'workshop.php',
                            'quizaccess_delaybetweenattempts.php', 'quizaccess_ipaddress.php', 'quizaccess_numattempts.php',
                            'quizaccess_openclosedate.php', 'quizaccess_password.php', 'quizaccess_safebrowser.php',
                            'quizaccess_securewindow.php', 'quizaccess_timelimit.php',
                            'competency.php', 'tool_lp.php', 'auth.php', 'langconfig.php', 'enrol_guest.php', 'block_myoverview.php',
                            'calendar.php', 'enrol_paypal.php', 'mimetypes.php', 'workshopform_accumulative.php',
                            'workshopform_comments.php', 'workshopform_numerrors.php', 'workshopform_rubric.php'
                            );

$numfound = 0;
$numnotfound = 0;
$notfound = array();

$files = scandir(JSON_FILES_PATH);
$languages = array();

foreach ($files as $f) {
    if (strpos($f, ".json")) {
        $languages[] = str_replace(".json", "", $f);
    }
}
$languages = array_unique($languages);

$englishids = file_get_contents(JSON_FILES_PATH . "en.json");
$englishids = (array) json_decode($englishids);
$englishids = array_keys($englishids);

$translated = array();

$forcedstrings = array(
    'mm.core.back' => 'moodle.php',
    'mm.login.login' => 'moodle.php',
    'mm.login.password' => 'moodle.php',
    'mma.myoverview.pluginname' => 'block_myoverview.php',
    'mm.core.download' => 'moodle.php',
    'mm.core.previous' => 'moodle.php',
    'mm.courses.paypalaccepted' => 'enrol_paypal.php',
    'mm.courses.sendpaymentbutton' => 'enrol_paypal.php',
    'mm.fileuploader.more' => 'data.php',
    'mm.fileuploader.invalidfiletype' => 'repository.php',
    'mm.user.details' => 'report_security.php',
    'mm.settings.settings' => 'moodle.php',
    'mm.settings.settings' => 'moodle.php',
    'mma.competency.learningplans' => 'tool_lp.php',
    'mma.competency.myplans' => 'tool_lp.php',
);

foreach ($moodlestringfiles as $stringfilename) {

    foreach ($languages as $lang) {
        if ($lang == "en") {
            continue;
        }

        $_lang = str_replace('-', '_', $lang);
        $stringfile = STRING_FILES_PATH . "$_lang/$stringfilename";
        if (!file_exists($stringfile)) {
            if (strpos($lang, '-') !== false) {
                $parentlang = substr($lang, 0, strpos($lang, '-'));
                $parentstringfile = STRING_FILES_PATH . "$parentlang/$stringfilename";
                if (file_exists($parentstringfile)) {
                    $stringfile = $parentstringfile;
                } else {
                    continue;
                }
            } else {
                continue;
            }
        }

        // Load Moodle string file.
        $string = array();
        include($stringfile);

        if (strpos($lang, '-') !== false) {
            $currentstrings = $string;
            $string = array();
            $parentlang = substr($lang, 0, strpos($lang, '-'));
            $parentstringfile = STRING_FILES_PATH . "$parentlang/$stringfilename";
            if (file_exists($parentstringfile)) {
                include($parentstringfile);
                $string = array_merge($string, $currentstrings);
            }
        }

        // Load app JSON file.
        $jsonfile = JSON_FILES_PATH . "$lang.json";
        $jsonstrings = file_get_contents($jsonfile);
        $jsonstrings = (array) json_decode($jsonstrings);

        // Missing strings.
        $found = false;
        foreach ($englishids as $id) {
            $force = false;
            if (strpos($id, ".") === false) {
                continue;
            }

            list($type, $component, $plainid) = explode('.', $id);

            if (isset($forcedstrings[$id])) {
                if ($stringfilename == $forcedstrings[$id] && !empty($string[$plainid])) {
                    $jsonstrings[$id] = str_replace('$a->', '$a.', $string[$plainid]);
                    $jsonstrings[$id] = str_replace('{$a', '{{$a', $jsonstrings[$id]);
                    $jsonstrings[$id] = str_replace('}', '}}', $jsonstrings[$id]);
                    // Prevent double.
                    $jsonstrings[$id] = str_replace(array('{{{', '}}}'), array('{{', '}}'), $jsonstrings[$id]);
                    // Missing application of [^{]{\$a\.([^}]*)}[^}]
                    $found = true;
                    $numfound++;
                    continue;
                } else {
                    continue;
                }
            }

            if ($component == 'myoverview') {
                if ($stringfilename != 'block_myoverview.php') {
                    continue;
                } else {
                    $force = true;
                }
            }

            if (strpos($plainid, 'mod_') !== false) {
                $modname = str_replace('mod_', '', $plainid);

                if ($modname == str_replace('.php', '', $stringfilename)) {
                    $found = true;
                    $numfound++;
                    if (empty($string['pluginname'])) {
                        //echo "Missing pluginname in $lang / $stringfilename \n";
                    } else if (empty($jsonstrings[$id])) {
                        $jsonstrings[$id] = $string['pluginname'];
                    }
                }
            }

            if ($stringfilename == 'mimetypes.php' && strpos($plainid, 'mimetype-') !== false) {
                $mimetypeid = str_replace('mimetype-', '', $plainid);
                if (!empty($string[$mimetypeid])) {
                    $jsonstrings[$id] = $string[$mimetypeid];
                }
                continue;
            }

            // We are translating and addon that is a Moodle module.
            $ismoodleplugin = false;
            if (strpos($component, 'mod_') !== false) {
                $modname = str_replace('mod_workshop_assessment', 'mod_workshopform', $component);
                $modname = str_replace('mod_assign_', 'mod_assign', $modname);
                $modname = str_replace('mod_', '', $modname);

                if ($modname == str_replace('.php', '', $stringfilename)) {
                    $ismoodleplugin = true;
                }
            }

            if (!empty($string[$plainid]) and !empty($jsonstrings[$id])) {
                if (trim($string[$plainid]) != trim($jsonstrings[$id]) && !isset($translated[$lang][$id])) {
                    $force = true;
                }
            }

            if (!empty($string[$plainid]) and ((empty($jsonstrings[$id]) or $ismoodleplugin) or $force)) {
                // print("$id found -> " . $string[$plainid] . " \n");
                $jsonstrings[$id] = str_replace('$a->', '$a.', $string[$plainid]);
                $jsonstrings[$id] = str_replace('{$a', '{{$a', $jsonstrings[$id]);
                $jsonstrings[$id] = str_replace('}', '}}', $jsonstrings[$id]);
                // Prevent double.
                $jsonstrings[$id] = str_replace(array('{{{', '}}}'), array('{{', '}}'), $jsonstrings[$id]);
                // Missing application of [^{]{\$a\.([^}]*)}[^}]
                $found = true;
                $numfound++;

                $translated[$lang][$id] = $id;
            } else if (empty($jsonstrings[$id])) {
                $notfound[$plainid][$lang] = "$stringfilename";
                $numnotfound++;
            }
        }

        if ($found) {
            // Order the array.
            ksort($jsonstrings);
            file_put_contents($jsonfile, str_replace('\/', '/', json_encode($jsonstrings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)));
        }

        // Split the translation.
        $componenttranslations = array();
        foreach ($jsonstrings as $key => $value) {
            list($type, $component, $plainid) = explode('.', $key);
            $componenttranslations["$type.$component"][$plainid] = $value;
        }

        foreach ($componenttranslations as $key => $strings) {
            list($type, $component) = explode('.', $key);
            if ($type == 'mma') {
                if (strpos($component, '_') !== false ) {
                    if (substr_count($component, '_') == 1) {
                        list($dir, $subdir) = explode('_', $component);
                        $component = $dir."/".$subdir;
                    } else {
                        list($dir, $subdir, $subdir2, $subdir3) = explode('_', $component);
                        $component = $dir."/".$subdir."/".$subdir2."/".$subdir3;
                    }
                }
                $path = CORE_FILES_PATH . "addons/$component/lang/$lang.json";
            } else {
                switch ($component) {
                    case 'core':
                        $path = CORE_FILES_PATH . "core/lang/$lang.json";
                        break;
                    default:
                        $path = CORE_FILES_PATH . "core/components/$component/lang/$lang.json";
                }
            }

            if (file_exists($path)) {
                $jsonstrings = file_get_contents($path);
                $jsonstrings = (array) json_decode($jsonstrings);
                $finalstrings = array_replace($jsonstrings, $strings);
            } else {
                $finalstrings = $strings;
            }

            $englishstrings = file_get_contents(str_replace("$lang.json", "en.json", $path));
            $englishstrings = (array) json_decode($englishstrings);
            // Remove strings that are not in master english.
            foreach ($finalstrings as $stringid => $stringcontent) {
                if (empty($englishstrings[$stringid])) {
                    unset($finalstrings[$stringid]);
                    //print("Removing string $stringid in language $lang\n\n");
                }
            }

            ksort($finalstrings);
            file_put_contents($path, str_replace('\/', '/', json_encode($finalstrings, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)));
        }
    }
}

ksort($notfound);
foreach ($notfound as $key => $nf) {
    // print("$key: ");
    // print(implode(' ', array_keys($nf)));
    // print("\n\n");
}
print("\n\nFound $numfound\n");
print("Not found $numnotfound\n\n");

// Exit 0 mean success.
exit(0);