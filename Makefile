.PHONY: build all test clean

build:
	php --define 'phar.readonly=0' ./bin/build.php

test:
	./test.sh

clean:
	rm -rf ./bin/*.phar*
