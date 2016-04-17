#!/bin/bash

clear

files=$(find ../ -mindepth 1 -maxdepth 2 -type f -name '*.php')

echo ""
echo "Running PHP Linter..."
echo ""

for i in $files; do
	php -l $i
done

echo ""
echo "Running PHPCodeSniffer with WordPress-Extra standards..."

ERRORS=0
for i in $files; do
	phpcs --standard="WordPress-Extra" $i

	if [ "$?" != 0 ]; then
		((ERRORS++))
	fi
done

if [ "$ERRORS" == 0 ]; then
	echo "All sniffs passed"
else
	echo "$ERRORS sniff(s) failed"
fi
