#!/usr/bin/env bash
echo "Starting webservice (lighttpd + php fast cgi)"

$( cd "$( dirname "$0" )" && /usr/bin/env lighttpd $* -f ./fast-cgi.conf )

