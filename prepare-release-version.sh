#!/bin/bash
#
# Script for preparing the release version (phonegapbuild compatible)
#

MASTER_PATH="/Users/juanleyvadelgado/Documents/MoodleMobile/moodlemobile2"
PB_PATH="/Users/juanleyvadelgado/Documents/MoodleMobile/moodlemobile-phonegapbuild"

cd $MASTER_PATH
git checkout master
git fetch moodlehq
git merge moodlehq/master master
gulp
cd $PB_PATH
git checkout master
cp -pr $MASTER_PATH/www/* $PB_PATH/
# Commit and push.
if [ $# -eq 1 ]
  then
    git commit -ma "Sync with master"
    git rebase master android
    git rebase master ios
    git push --force
fi
#sed -e 's/version   = "[^"]*"/version   = ""/' $PB_PATH/config.xml > index.html.tmp && mv index.html.tmp index.html
#sed -e 's/versionCode = "[^"]*"/versionCode = ""/' $PB_PATH/config.xml
#sed -e 's/<string>[^<]*"/<string>/' $PB_PATH/config.xml