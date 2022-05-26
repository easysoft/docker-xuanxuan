#!/bin/bash

# shellcheck disable=SC1091

set -o errexit
set -o nounset
set -o pipefail

[ -n "${DEBUG:+1}" ] && set -x

# Load libraries
. /opt/easysoft/scripts/liblog.sh
. /opt/easysoft/scripts/libeasysoft.sh

# Load Envs
. /etc/s6/s6-init/envs

print_welcome_page

# make mysql start
[ ! -L /etc/s6/s6-enable/00-mysql ] && ln -sf /etc/s6/s6-available/mysql /etc/s6/s6-enable/00-mysql

# make apache start
[ ! -L /etc/s6/s6-enable/01-apache ] && ln -sf /etc/s6/s6-available/apache /etc/s6/s6-enable/01-apache

# make xxd start
[ ! -L /etc/s6/s6-enable/02-xxd ] && ln -sf /etc/s6/s6-available/xxd /etc/s6/s6-enable/02-xxd

if [ $# -gt 0 ]; then
    exec "$@"
else
    # Init service
    /etc/s6/s6-init/run || exit 1

    # Start s6 to manage apache and mysql
    exec /usr/bin/s6-svscan /etc/s6/s6-enable
fi
