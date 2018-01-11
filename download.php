<?php

define("LAST_VERSION", "3.4.0");

$platform = $_GET['platform'];

if (isset($_GET['version'])) {
    $version = $_GET['version'];
} else {
    $version = LAST_VERSION;
}

if (isset($_GET['arch'])) {
    $_GET['arch'] = (int) $_GET['arch'];
    $arch = '-' . $_GET['arch'];
} else {
    $arch = '';
}

$plainversion = 'v' . str_replace('.', '', $version);
$app = 'moodledesktop';
switch ($platform) {
    case 'windows':
        $extension = 'zip';
        break;
    case 'linux':
        $extension = 'tar.gz';
        break;
    case 'android':
        $app = 'moodlemobile';
        $extension = 'apk';
        break;
    default:
        die('Invalid platform');
}

$month = date('Y-m');
$logfile = 'stats/' . $platform . '-' .$month . '.log';

$fulldate = date('Y-m-d H:i:s');
$line = $version . $arch . ' - ' . $fulldate . " - $_SERVER[REMOTE_ADDR] - " . $_SERVER['HTTP_USER_AGENT'];
file_put_contents($logfile, $line . PHP_EOL, FILE_APPEND);

$downloadurl = "https://download.moodle.org/desktop/$platform/$app-$platform$arch-$plainversion.$extension";
header("Location: $downloadurl");
die;