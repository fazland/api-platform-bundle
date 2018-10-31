#!/usr/bin/env bash

DEBUG=${DEBUG:-0}

if [ ! -f dep/kmyacc/src/kmyacc ] ; then
    git submodule update --init --recursive
    pushd dep/kmyacc
    make
    popd
fi

DEBUG_FLAG=
if [ "${DEBUG}" == "1" -o "${DEBUG}" == "yes" ] ; then
    DEBUG_FLAG=-t
fi

dep/kmyacc/src/kmyacc ${DEBUG_FLAG} -L php -m src/QueryLanguage/Grammar/template.parser.php src/QueryLanguage/Grammar/AbstractGrammar.y
sed 's/\/\/ \$namespace/namespace Fazland\\ApiPlatformBundle\\QueryLanguage\\Grammar;/' src/QueryLanguage/Grammar/AbstractGrammar.php > src/QueryLanguage/Grammar/grammar.php.tmp
mv src/QueryLanguage/Grammar/grammar.php.tmp src/QueryLanguage/Grammar/AbstractGrammar.php
