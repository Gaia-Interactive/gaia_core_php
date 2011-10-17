GAIA CORE PHP SHARED LIBRARIES
==============================
The components in this library are open sourced versions of code patterns that have been working for Gaia Interactive for years.

Continued at the project [Wiki Home](https://github.com/gaiaops/gaia_core_php/wiki/).


REQUIREMENTS
==============================
This library was designed to run on top of a LAMP (Linux, Apache 2.x+, some modern popular open-source RDBMS such as MySQL 5+, and PHP 5.3+) stack.
You are expected to have installed Apache2 with mod_rewrite, PHP 5.3+ with the database and cache drivers that you expect to run (such as MySQLi, Memcache / Memcached, APC, etc).
Make sure php has bcmath and mbstring enabled.

GETTING STARTED
==============================
Peruse the [Top level Module Documentation](https://github.com/gaiaops/gaia_core_php/wiki/_pages).

In the future, we may try to make the library PHPDoc compliant.


BRANCHES
==============================
Each sub-component of the gaia library has its own branch. We create a dependency tree in `branches/dependencies`. You can use the entire library by using master, or just checkout the branches you want to use.

Here is an example. Suppose I need to use the nonce class and the container class only. I create a new branch based off of the framework which only has the bare structure:

    git checkout framework
    git checkout -b mybranch
    git merge container
    git merge nonce
    
This will give me only the code needed for these two components. At any point later I can pull in other branches as I need them.


TESTING
==============================
All of the tests were written using the [Gaia\Test\Tap class]
(https://github.com/gaiaops/gaia_core_php/blob/master/lib/gaia/test/tap.php). They produce 
[TAP](http://en.wikipedia.org/wiki/Test_Anything_Protocol) output 
which is both human-readable and can be run from the command line on its own as a stand-alone executable 
file. In addition, it can be parsed by perl's prove harness to run all the tests together.

Try it out by running ./test.sh 



CONTACT
==============================
Questions, concerns, and guidance: project lead [John Loehrer](mailto:jloehrer@gaiaonline.com)

LICENSE
==============================
see the LICENSE file at the root of the repo
