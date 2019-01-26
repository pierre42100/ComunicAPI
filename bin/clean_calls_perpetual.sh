#!/bin/bash

# Clean call on a long period
# This script is made to be used on dev machines only
# @author Pierre HUBERT

# Place us in current directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd $SCRIPT_DIR;

while true; do
	./clean_calls
	sleep 30;
done