#!/bin/sh
echo "Note: Make sure you have the test environment set up, since phan will analyze the tests and thus the test env is needed."
vendor/bin/phan --no-progress-bar --allow-polyfill-parser
