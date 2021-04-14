#!/bin/sh
vendor/bin/phplint ./ --no-progress --exclude=vendor --exclude=tests/bin --exclude=src/Dependencies
