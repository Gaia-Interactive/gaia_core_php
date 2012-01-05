.PHONY: build all test clean

build:
	./build_phar.sh

test:
	./test.sh

clean:
	rm -rf ./gaia_core_php.phar*
