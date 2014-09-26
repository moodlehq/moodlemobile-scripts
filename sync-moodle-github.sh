#!/bin/bash
#
# Sync github branches with Moodle upstream ones
BASE_DIR=/Users/juanleyvadelgado/www/moodlebugs

cd $BASE_DIR
git fetch upstream
for BRANCH in MOODLE_26_STABLE MOODLE_27_STABLE master; do
   git push origin refs/remotes/upstream/$BRANCH:$BRANCH
done

