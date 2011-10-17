#!/bin/bash
branch_name="$(git symbolic-ref HEAD 2>/dev/null)" || branch_name="(unnamed branch)"     # detached HEAD
branch_name=${branch_name##refs/heads/}

git fetch origin || exit 1;

for branch in `git branch -a | grep remotes/origin/ | grep -v HEAD`; do
    branch="${branch##*/}"
    echo "merging $branch from origin..."
    git checkout "$branch" || exit 1;
    git merge "origin/$branch" || exit 1;
done

git checkout $branch_name
echo "ALL DONE!"
