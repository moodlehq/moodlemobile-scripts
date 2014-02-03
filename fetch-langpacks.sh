#!/bin/bash
#
# Tool for automatic synchronization of language strings translated in AMOS (lang.moodle.net)
# The strings to be translated are uploaded periodically to the Moodle plugins database via this plugin:
# https://github.com/jleyva/moodle-local_moodlemobileapp
#

# GIT FILE PATH
# http://git.moodle.org/gw?p=moodle-langpacks.git;a=tree;h=refs/heads/MOODLE_26_STABLE;hb=refs/heads/MOODLE_26_STABLE
GIT_PATH="/Users/juanleyvadelgado/Documents/MoodleMobile/moodle-langpacks/moodle-langpacks"
# Branch where the translation plugin is placed
BRANCH="MOODLE_26_STABLE"
# Directory where Moodle Mobile lang files are located
LANG_DIR="/Users/juanleyvadelgado/Documents/MoodleMobile/GIT/lang"
# Minimun number of strings translated the language pack should have
LINES_MIN=150

if [ $# -eq 0 ]
  then
    echo "./fetch-langpacks.sh days"
    exit 1
fi

cd $GIT_PATH
git checkout $BRANCH
git pull
# Lood for language files recently updated
packs=`find . -type f -name "local_moodlemobileapp.php" -mtime -$1`
for f in $packs
do
  lines=`cat $f | grep "string" | wc -l`
  if [ $lines -gt $LINES_MIN ]
  then
    lang=`echo $f | sed "s/\.\///" | sed "s/\/.*//"`
    if [ $lang != "en" ]
    then
      # Convert a Moodle php lang file to a Moodle Mobile json lang file
      file=$(cat $f | grep "\$string" | sed "s/\\\'/'/g" | sed "s/\$string\['/\"/g" | sed "s/'\] = '/\": \"/g" | sed "s/';/\",/g")
      # Create a valid json file
      echo "{$file}" | sed "s/,}/}/g"  > $LANG_DIR/$lang.json
      echo "File $lang.json created"
    fi
  fi
done
