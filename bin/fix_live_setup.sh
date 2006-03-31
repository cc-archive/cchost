#!/bin/bash
#
# Fixes the current setup so that ccHost will work. You have to move the 
# ccadmin folder out of the way and also, if we have a backed up config file
# then this script makes it default and saves the old one.
#


CURR_DATE=`date +%F`


if [ -e "ccadmin" ]
then :
    if [ -e "ccadmin_backup" ]
    then :
        mv ccadmin_backup ccadmin_backup_${CURR_DATE}
    fi
    mv ccadmin ccadmin_old
fi
    
if [ -e "cc-config-db.php.backup" ]
then : 

    if [ -e "cc-config-db.php" ]
    then :
        mv cc-config-db.php cc-config-db.php.backup_${CURR_DATE}
    fi
    mv cc-config-db.php.backup cc-config-db.php
fi


