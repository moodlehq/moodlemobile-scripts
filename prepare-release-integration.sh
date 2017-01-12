#!/bin/bash
#
# Script for preparing the release version (phonegapbuild compatible)
#

MASTER_PATH="/Users/juanleyvadelgado/Documents/MoodleMobile/moodlemobile2"
PB_PATH="/Users/juanleyvadelgado/Documents/MoodleMobile/moodlemobile-phonegapbuild"

cd $MASTER_PATH
git checkout integration
git fetch moodlehq
git reset --hard moodlehq/integration
bower install
gulp
cd $PB_PATH
git checkout integration
cp -pr $MASTER_PATH/www/* $PB_PATH/
# Delete not-required files.
find addons -name "*.js" -type f -delete
find core -name "*.js" -type f -delete
find addons -name "*.json" -type f -delete
find core -name "*.json" -type f -delete
find addons -name "*.scss" -type f -delete
find core -name "*.scss" -type f -delete
find . -name "*bower.json" -type f -delete
find . -name "*.md" -type f -delete
find . -name "*README*" -type f -delete
find . -name "*LICENSE*" -type f -delete
find . -name "*.gzip" -type f -delete
find . -name ".gitignore" -type f -delete
find lib -name "package.json" -type f -delete
rm -rf lib/ionic/demos
rm -rf lib/angular-md5/example
rm -rf lib/angular-ui-router/src
rm -rf lib/moment/src
rm -rf lib/ionic/scss
rm -rf lib/ckeditor/samples
rm -rf lib/jszip/docs
rm -rf lib/jszip/documentation
rm -rf lib/ydn.db/test
rm -rf lib/ydn.db/example
rm -rf lib/ydn.db/src
rm -rf  lib/chart.js/docs
rm -rf  lib/chart.js/test
rm -rf  lib/chart.js/src
rm -rf  lib/chart.js/samples
rm -rf  lib/chart.js/scripts
cp -pr $MASTER_PATH/www/core/assets/* $PB_PATH/core/assets/
# Commit and push.
if [ $# -eq 1 ]
  then
    git commit -am "Sync with master"
    git rebase master android
    git rebase master ios
    git push --force
fi
#sed -e 's/version   = "[^"]*"/version   = ""/' $PB_PATH/config.xml > index.html.tmp && mv index.html.tmp index.html
#sed -e 's/versionCode = "[^"]*"/versionCode = ""/' $PB_PATH/config.xml
#sed -e 's/<string>[^<]*"/<string>/' $PB_PATH/config.xml