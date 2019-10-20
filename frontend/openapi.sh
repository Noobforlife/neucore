#!/usr/bin/env bash

DIR=$(dirname "$(realpath "$0")")

# generate the OpenAPI client

VERSION=4.1.3
FILENAME=openapi-generator-cli-${VERSION}.jar

if [[ ! -f ${DIR}/${FILENAME} ]]; then
    wget http://central.maven.org/maven2/org/openapitools/openapi-generator-cli/${VERSION}/${FILENAME} \
        -O ${DIR}/${FILENAME}
fi

rm -Rf ${DIR}/neucore-js-client/*

java -jar ${DIR}/${FILENAME} generate \
    -c ${DIR}/neucore-js-client-config.json \
    -i ${DIR}/../web/frontend-api-3.yml \
    -g javascript \
    -o ${DIR}/neucore-js-client \
    --skip-validate-spec
