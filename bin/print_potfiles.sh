#!/bin/bash

#
# This script looks to find files which use gettext to do language calls. 
# The recommended usage is to run this script and redirect to a file like
# POTFILES.in for then editing to make sure you have the files you want.
#

echo '# List of source files containing translatable strings.
# Please keep this file sorted alphabetically.
# If majorly out of order, use bin/print_potfiles.sh to regenerate
[encoding: UTF-8]'

find . -name "*.php" -o -name "*.inc" | xargs grep -l "_(.*)" | sort | sed -e 's/\.\///'

# TO ADD ' \' onto end of lines, add -e 's/$/ \\/'

