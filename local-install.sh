#!/bin/sh

. .env
cp -r ./RetentionFactory $MAGENTO_HOME/app/code/local
cp -r ./app/etc $MAGENTO_HOME/app