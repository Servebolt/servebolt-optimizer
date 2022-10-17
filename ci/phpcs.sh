#!/bin/sh
vendor/bin/phpcs --version
vendor/bin/phpcs -s --standard=PSR1 \
--exclude=Squiz.CSS.Indentation \
--ignore=*.min* \
src/Servebolt tests/Unit assets/src "$*"
