#!/bin/bash
#
# Creative Commons has made the contents of this file
# available under a CC-GNU-GPL license:
# 
# http://creativecommons.org/licenses/GPL/2.0/
# 
# A copy of the full license can be found as part of this
# distribution in the file COPYING.
# 
# You may use the ccHost software in accordance with the
# terms of that license. You agree that you are solely 
# responsible for your use of the ccHost software and you
# represent and warrant to Creative Commons that your use
# of the ccHost software will comply with the CC-GNU-GPL.
# 
# $Id$
# 
# Copyright 2005-2006, Creative Commons, www.creativecommons.org.
# Copyright 2006, Jon Phillips, jon@rejon.org.
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
chown ${USERNAME}:${WWWGROUP} ../*
chmod 775 ./
chown -Rf ${USERNAME}:${WWWGROUP} cclib/phptal/phptal_cache
chmod 775 cclib/phptal/phptal_cache
chmod 775 cctemplates
chown ${USERNAME}:${WWWGROUP} cctemplates
chmod 775 locale
chown -Rf ${USERNAME}:${WWWGROUP} locale
