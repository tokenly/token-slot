#!/bin/bash

set -e

# install composer
echo; echo "install composer"
/usr/local/bin/composer.phar install --prefer-dist --no-progress

