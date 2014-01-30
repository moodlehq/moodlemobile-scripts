#!/bin/bash
GIT_BASE=/Users/juanleyvadelgado/Documents/MoodleMobile/GIT
KEYSTORE=/Users/juanleyvadelgado/Documents/MoodleMobile/moodlemobile.keystore
EMAIL=mobile@cvaconsulting.com

rm MoodleMobile$1.apk
rm MoodleMobile$1Store.apk
rsync -a --exclude='.*' $GIT_BASE/ tmpdir/assets/www/
../apktool b tmpdir MoodleMobile$1.apk
jarsigner -verbose -sigalg SHA1withRSA -digestalg SHA1 -keystore $KEYSTORE  MoodleMobile$1.apk moodlemobile
zipalign -v 4 MoodleMobile$1.apk MoodleMobile$1Store.apk
echo "Moodle Mobile apk" | mutt -a "MoodleMobile$1Store.apk" -s "MoodleMobile$1Store.apk" -- $EMAIL
