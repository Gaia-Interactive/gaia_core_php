#!/usr/bin/env bash
echo "Starting webservice (lighttpd + php fast cgi)"

/usr/bin/env lighttpd $* -f ./fast-cgi.conf