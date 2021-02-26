#!/bin/sh
vendor/bin/phpcs --version
vendor/bin/phpcs -s --standard=PSR2 \
--exclude=Squiz.CSS.Indentation \
src tests "$*"
