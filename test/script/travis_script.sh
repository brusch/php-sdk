#!/usr/bin/env bash

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

# run static php-cs-fixer code analysis
./vendor/bin/php-cs-fixer fix --dry-run --diff --verbose

## enable xdebug again
mv ~/.phpenv/versions/$(phpenv version-name)/xdebug.ini.bak ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/xdebug.ini

## run the tests (todo: enable)
# ./vendor/bin/phpunit test/unit --coverage-xml log/xml/

## perform this task only for php 7 with deps=no
if [[ ($(phpenv version-name) == "7.0") && ("$deps" == "no") ]]; then
    echo "Perform integration tests";
    #./vendor/bin/phpunit test/integration
    ./vendor/bin/phpunit test/integration --coverage-xml log/xml/
fi
