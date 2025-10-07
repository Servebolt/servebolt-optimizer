#!/bin/bash
mkdir servebolt-optimizer
rsync -av --progress ./ servebolt-optimizer --exclude servebolt-optimizer --exclude servebolt-optimizer.zip --exclude vendor --exclude node_modules --exclude tests --exclude .git --exclude .DS_Store
cd servebolt-optimizer
composer install --no-dev --optimize-autoloader
yarn install
yarn production
./ci/cleanup.sh
cd ..
zip -r -9 servebolt-optimizer.zip servebolt-optimizer
rm -rf servebolt-optimizer
