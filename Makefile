.PHONY: build all test clean

build:
	./bin/build.php

test:
	./test.sh

clean:
	rm -rf ./bin/*.phar*
