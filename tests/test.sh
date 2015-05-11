#!/bin/bash

clear

files=$(find ../ -mindepth 1 -maxdepth 1 -type f -name '*.php')

echo "Running PHP Linter..."
echo ""
for i in $files; do
	php -l $i
done
echo ""
echo "Running PHPCodeSniffer..."
for i in $files; do
	phpcs --standard="WordPress-Extra" $i
done
