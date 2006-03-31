#!/bin/bash

#
# Fixes permissions so that the web server and the currently logged in user 
# have all the authority.
#

echo "What username do you want the files to have? [DEFAULT = `whoami`]"
read USERNAME
echo "What is your webserver's group? [DEFAULT = apache]"
read WWWGROUP 

if [ -z "$USERNAME" ]
then :
    USERNAME=`whoami`
fi

if [ -z "$WWWGROUP" ]
then :
    WWWGROUP=apache
fi

# Make sure that you are in the group apache
chown -Rf ${USERNAME}:${WWWGROUP} ../*

