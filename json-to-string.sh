#!/bin/bash
#
# Conversion tool, from .json lang file to Moodle lang file format.
#2

# JSON file to be converted
JSON_FILE="/Users/juanleyvadelgado/Documents/MoodleMobile/GIT/lang/en.json"
# Destination file
STRING_FILE="/Users/juanleyvadelgado/Documents/MoodleMobile/moodle-local_moodlemobileapp/lang/en/local_moodlemobileapp.php"

header=`cat $STRING_FILE | head -40`

echo "$header" > $STRING_FILE

cat $JSON_FILE | sed "s/'/\\\'/g" | sed 's/"\([^"]*\)" *: *"\([^"]*\)",*/$string[##\1##] = ##\2##;/' \
| sed "s/##/\'/g" | sed 's/}$//g' | sed 's/^{//g' >> $STRING_FILE


