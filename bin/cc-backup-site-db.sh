#!/bin/bash

#
# Script for backing up your cchost database
#
# Usage:
#
#    $ DBUSER=editor ./backup_site_db.sh
#
#
#


if [ -z ${DBHOST} ]; then
    DBHOST=localhost
fi

if [ -z ${DBUSER} ]; then
    DBUSER=localuser
fi

if [ -z ${DBTABLE} ]; then
    DBTABLE=cchost
    #DBTABLE=--all-databases
fi

mysqldump -h ${DBHOST} -u ${DBUSER} -p ${DBTABLE} > mysql_dump_${DBTABLE}_`date +%F`.sql

exit 0
