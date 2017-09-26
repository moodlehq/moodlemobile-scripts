<?php

$platform = $_GET['platform'];
$version = $_GET['version'];

$plainversion = 'v' . str_replace('.', '', $version);
switch ($platform) {
    case 'windows':
        $extension = 'zip';
        break;
    case 'linux':
        $extension = 'tar.gz';
        break;
    default:
        die('Invalid platform');
}

$month = date('Y-m');
$logfile = 'stats/' . $platform . '-' .$month . '.log';

$fulldate = date('Y-m-d H:i:s');
$line = $version . ' - ' . $fulldate . " - $_SERVER[REMOTE_ADDR] - " . $_SERVER['HTTP_USER_AGENT'];
file_put_contents($logfile, $line . PHP_EOL, FILE_APPEND);

$downloadurl = "https://download.moodle.org/desktop/$platform/moodledesktop-$platform-$plainversion.$extension";
header("Location: $downloadurl");
die;