#!/bin/sh
vendor/bin/phpcs --version
vendor/bin/phpcs -s --standard=PSR1 \
--exclude=Squiz.CSS.Indentation \
src/Servebolt tests/Unit tests/Feature assets/src "$*"
