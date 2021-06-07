#!/bin/bash
mkdir build
rsync -av --progress ./ build --exclude build --exclude vendor --exclude node_modules --exclude tests --exclude .git
cd build
composer install --no-dev --optimize-autoloader
yarn install
yarn production
./cleanup.sh
rm cleanup.sh
cd ..
zip -r -9 servebolt-optimizer.zip build
rm -rf build
