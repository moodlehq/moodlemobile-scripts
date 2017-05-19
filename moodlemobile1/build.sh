#!/bin/bash
#
# Tool for building the app for iOs AND ANDROID using Phonegap Build Developers API
#

# Where to get notified
EMAIL=mobile@cvaconsulting.com

if [ $# -lt 7 ]
  then
    echo "./ios-build.sh token appid IOSkeyid IOSkeypassword ANDROIDkeyid ANDROIDkeypw ANDROIDkeystorepw"
    exit 1
fi

TOKEN=$1
APPID=$2
IOS_KEY_ID=$3
IOS_KEY_PASSWORD=$4
ANDROID_KEY_ID=$5
ANDROID_KEY_PW=$6
ANDROID_KEYSTORE_PW=$7

curl -X PUT -d \
  "data={\"pull\":\"true\", \"keys\": { \
  \"ios\":{\"id\": \"$IOS_KEY_ID\", \"password\": \"$IOS_KEY_PASSWORD\"}, \
  \"android\":{\"id\": \"$ANDROID_KEY_ID\", \"key_pw\": \"$ANDROID_KEY_PW\", \"keystore_pw\": \"$ANDROID_KEYSTORE_PW\"} \
  }}" \
  https://build.phonegap.com/api/v1/apps/$APPID?auth_token=$TOKEN

date=`date`
echo "https://build.phonegap.com/apps/$APPID/builds" | mutt -s "Apps builds $date" -- $EMAIL
