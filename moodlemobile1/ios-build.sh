#!/bin/bash
#
# Tool for building the app for iOs using Phonegap Build Developers API
#

# Where to get notified
EMAIL=mobile@cvaconsulting.com

if [ $# -lt 4 ]
  then
    echo "./ios-build.sh token appid keyid keypassword"
    exit 1
fi

TOKEN=$1
APPID=$2
KEY_ID=$3
KEY_PASSWORD=$4

curl -X PUT -d \
  "data={\"pull\":\"true\", \"keys\": {\"ios\":{\"id\": \"$KEY_ID\", \"password\": \"$KEY_PASSWORD\"}}}" \
  https://build.phonegap.com/api/v1/apps/$APPID?auth_token=$TOKEN


echo "https://build.phonegap.com/apps/$APPID/builds" | mutt -s "MoodleMobile$1Store.ipa" -- $EMAIL
