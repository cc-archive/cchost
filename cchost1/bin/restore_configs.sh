#!/bin/bash

#
# Restores any backed up config files so that they are the primary configs.
#

CURR_DATE=`date +%F`


if [ -e "ccadmin" ]
then :
    mv ccadmin ccadmin_old
fi

if [ -e "ccadmin_backup" ]
then :
    mv ccadmin_backup ccadmin
fi

if [ -e "cc-config-db.php" ]
then : 
    mv cc-config-db.php cc-config-db.php.old
fi


if [ -e "cc-config-db.php.backup" ]
then : 
    mv cc-config-db.php.backup cc-config-db.php
fi
