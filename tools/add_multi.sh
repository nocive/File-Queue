#!/bin/bash
SCRIPT_PATH=$(readlink -f $(dirname $0))
APP_PATH=$(readlink -f $SCRIPT_PATH/../)
SCRIPT=$APP_PATH/tools/add.php

cd $APP_PATH
php $SCRIPT 2>&1 >> add_multi.log &
php $SCRIPT 2>&1 >> add_multi.log &
php $SCRIPT 2>&1 >> add_multi.log &
php $SCRIPT 2>&1 >> add_multi.log &
php $SCRIPT 2>&1 >> add_multi.log &

php $SCRIPT 2>&1 >> add_multi.log &
php $SCRIPT 2>&1 >> add_multi.log &
php $SCRIPT 2>&1 >> add_multi.log &
php $SCRIPT 2>&1 >> add_multi.log &
php $SCRIPT 2>&1 >> add_multi.log &

php $SCRIPT 2>&1 >> add_multi.log &
php $SCRIPT 2>&1 >> add_multi.log &
php $SCRIPT 2>&1 >> add_multi.log &
php $SCRIPT 2>&1 >> add_multi.log &
php $SCRIPT 2>&1 >> add_multi.log &

php $SCRIPT 2>&1 >> add_multi.log &
php $SCRIPT 2>&1 >> add_multi.log &
php $SCRIPT 2>&1 >> add_multi.log &
php $SCRIPT 2>&1 >> add_multi.log &
php $SCRIPT 2>&1 >> add_multi.log &
