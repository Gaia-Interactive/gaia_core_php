#!/usr/bin/env bash
echo "Starting webservice (lighttpd + php fast cgi)"

/usr/bin/env lighttpd -D $* -f ./fast-cgi.conf