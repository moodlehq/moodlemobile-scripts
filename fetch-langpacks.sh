#!/bin/bash
# GIT FILE PATH
GIT_PATH="/Users/juanleyvadelgado/Documents/MoodleMobile/moodle-langpacks/moodle-langpacks"
BRANCH="MOODLE_26_STABLE"
LANG_DIR="/Users/juanleyvadelgado/Documents/MoodleMobile/GIT/lang"
# Minimun number of strings translated the language pack should have
LINES_MIN=150

if [ $# -eq 0 ]
  then
    echo "Missing file modified time number of days to look for"
    exit 1
fi

cd $GIT_PATH
git checkout $BRANCH
git pull
packs=`find . -type f -name "local_moodlemobileapp.php" -mtime -$1`
for f in $packs
do
  lines=`cat $f | grep "string" | wc -l`
  if [ $lines -gt $LINES_MIN ]
  then
    lang=`echo $f | sed "s/\.\///" | sed "s/\/.*//"`
    if [ $lang != "en" ]
    then
      file=$(cat $f | grep "\$string" | sed "s/\\\'/'/g" | sed "s/\$string\['/\"/g" | sed "s/'\] = '/\": \"/g" | sed "s/';/\",/g")
      echo "{$file}" | sed "s/,}/}/g"  > $LANG_DIR/$lang.json
      echo "File $lang.json created"
    fi
  fi
done
