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
# This script updates the current installation to the latest in svn and moves
# out of the way the ccadmin folder if the cc-config-db.php file exists
#

CCADMIN_DIR=ccadmin
CCADMIN_BACKUP_DIR=${CCADMIN}_backup

# if [ ! -e "$CCADMIN_DIR" ] && [ -e "${CCADMIN_BACKUP}" ]; then
#    mv $CCADMIN_BACKUP_DIR $CCADMIN_DIR
#else;
svn up
#fi
if [ -e Makefile ]; then
    make
fi
svn ci

exit 0
