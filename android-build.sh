#!/bin/bash
#
# Automatic building system for Android
# We use and old APK for building, we extrack the apk files, replace the www/files and then package and sign again
#

DIR_BASE=/Users/juanleyvadelgado/Documents/MoodleMobile/apktool
GIT_BASE=/Users/juanleyvadelgado/Documents/MoodleMobile/GIT
KEYSTORE=/Users/juanleyvadelgado/Documents/MoodleMobile/moodlemobile.keystore
EMAIL=mobile@cvaconsulting.com

if [ $# -eq 0 ]
  then
    echo "Missing version argument (133, 134...)"
    exit 1
fi

# This directory must exists and also a tmpdir with the Android packacge uncompressed
# TODO: Improve this to make all the steps automatic
cd $DIR_BASE/moodlemobile$1
rm MoodleMobile$1.apk
rm MoodleMobile$1Store.apk
# Copy Moodle Mobile files to the Android package
rsync -a --exclude='.*' $GIT_BASE/ tmpdir/assets/www/
# Build the apk again
../apktool b tmpdir MoodleMobile$1.apk
# Sign the apk, this prompts for passwords
jarsigner -verbose -sigalg SHA1withRSA -digestalg SHA1 -keystore $KEYSTORE  MoodleMobile$1.apk moodlemobile
# Compress the apk (mandatory for upload the apk to the Google Play store)
zipalign -v 4 MoodleMobile$1.apk MoodleMobile$1Store.apk
# Send by email the new apk, to be tested in a Phone (and for historic also)
echo "Moodle Mobile apk" | mutt -a "MoodleMobile$1Store.apk" -s "MoodleMobile$1Store.apk" -- $EMAIL
