#!/usr/bin/env bash
prove_installed=`which prove`

if [ ! "$prove_installed" ]
then
    echo "prove executable not found. please install perl-Test-Harness."
    exit 1;
fi

php_installed=`which php`

if [ ! "$php_installed" ]
then
    echo "php executable not found. please install php5.3 or greater."
    exit 1;
fi


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

execsupported=$(prove -? | grep "\--exec")

if [ "$execsupported" ]; then
    /usr/bin/env prove -r --exec=php $* $f
else
    /usr/bin/env prove -r $* $f
fi