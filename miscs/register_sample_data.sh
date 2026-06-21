#!/bin/bash
set -eu

source ./tests/secrets.sh
source ./_cf-common/test/export_secrets.sh ${SECRETS[*]}

# Launch function
APP_ENV=local php ./miscs/register_sample_data.php

source ./_cf-common/test/unset_secrets.sh ${SECRETS[*]}
