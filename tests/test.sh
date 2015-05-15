#!/bin/bash

clear

files=$(find ../ -mindepth 1 -maxdepth 2 -type f -name '*.php')

echo "Removing any surrounding spaces between array keys that contain a string or an integer..."

for i in $files; do
	echo "Formatting $i"
	cp $i $i.orig
	# This regex replaces [ 0 ] with [0], [ 123 ] with [123], [ "ID" ] with ["ID"] and [ 'ID' ] with ['ID']
	sed -ri "s/\[ ([0-9]+|(\x27|\x22)[^\x27\x22]*(\x27|\x22)) \]/[\1]/g" $i
	# Delete the backup if no changes were made
	cmp --silent post-lockdown.php post-lockdown.php.orig && rm -f $i.orig
done

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
