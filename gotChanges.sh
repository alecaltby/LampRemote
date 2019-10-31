#!/bin/bash
#git fetch

HEAD=$(git rev-parse HEAD)
ORIGIN=$(git rev-parse origin/master)

if [[ $HEAD != $ORIGIN ]]; then
	exit 0
else
	exit 1
fi
