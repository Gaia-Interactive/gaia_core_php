#!/usr/bin/env bash

version=`/usr/bin/env php -r "echo phpversion();"`
versioncheck=`/usr/bin/env php -r "echo version_compare(phpversion(), '5.3');"`

if [ $versioncheck -lt 1 ]
then
    echo "php5.3+ required. you are running $version. please upgrade."
    exit 1;
fi

echo "gaia_core_php tests running php $version ..."
echo "-----------------------------------"


f=`dirname $0`
/usr/bin/env prove -r --exec=php $* $f

