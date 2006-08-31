#!/bin/bash

#
# Makes backups of the various configs that might get overwritten from updating
# to the latest version of the code.
#

CURR_DATE=`date +%F`

if [ -e "ccadmin_backup" ]
then :
    rm -Rf ccadmin_backup
fi

if [ -e "ccadmin" ]
then :
    mv ccadmin ccadmin_backup
fi

if [ -e "cc-config-db.php" ]
then : 
    mv cc-config-db.php cc-config-db.php.backup
fi
