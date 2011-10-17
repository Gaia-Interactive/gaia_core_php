#!/usr/bin/env sh
DIR=`dirname $0`
DEBUG=""
branch_name="$(git symbolic-ref HEAD 2>/dev/null)" || branch_name="(unnamed branch)"     # detached HEAD
branch_name=${branch_name##refs/heads/}

echo "currently in branch $branch_name"
DIR_DEPEND="${DIR}/dependencies"
FILES="${DIR_DEPEND}/*"
shopt -s nullglob
for f in $FILES
do
    b=`basename "$f"`

    echo "merging $b ..."
    OUT=`git checkout "$b" 2>&1`
    RESULT=$?
    if [ $RESULT != 0 ]; then
        echo "ERROR ..."
        echo "$OUT"
        echo "EXITING ..."
        exit 1;
    fi
    
    #echo "running ${0} $* on branch $b"
    OUT=`${0} $* 2>&1`
    RESULT=$?
    if [ $RESULT != 0 ]; then
        echo "ERROR ..."
        echo "$OUT"
        echo "EXITING ..."
    fi
    
    OUT=`git checkout "$branch_name" 2>&1`
    RESULT=$?
    if [ $RESULT != 0 ]; then
        echo "ERROR ..."
        echo "$OUT"
        echo "EXITING ..."
    fi
    
    #echo "merging $b back into $branch_name..."
    OUT=`git merge "$b" 2>&1`
    RESULT=$?
    if [ $RESULT != 0 ]; then
        echo "ERROR ..."
        echo "$OUT"
        echo "EXITING ..."
    fi
done

echo "DONE!"

exit 0