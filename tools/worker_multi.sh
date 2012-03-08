#!/bin/bash
SCRIPT_PATH=$(dirname $( readlink -f $0))
APP_PATH=$(readlink -f $SCRIPT_PATH/../)
SCRIPT=$APP_PATH/tools/worker.php

cd $APP_PATH
php $SCRIPT 2>&1 >> workers.log &
php $SCRIPT 2>&1 >> workers.log &
php $SCRIPT 2>&1 >> workers.log &
php $SCRIPT 2>&1 >> workers.log &
php $SCRIPT 2>&1 >> workers.log &

php $SCRIPT 2>&1 >> workers.log &
php $SCRIPT 2>&1 >> workers.log &
php $SCRIPT 2>&1 >> workers.log &
php $SCRIPT 2>&1 >> workers.log &
php $SCRIPT 2>&1 >> workers.log &

php $SCRIPT 2>&1 >> workers.log &
php $SCRIPT 2>&1 >> workers.log &
php $SCRIPT 2>&1 >> workers.log &
php $SCRIPT 2>&1 >> workers.log &
php $SCRIPT 2>&1 >> workers.log &

php $SCRIPT 2>&1 >> workers.log &
php $SCRIPT 2>&1 >> workers.log &
php $SCRIPT 2>&1 >> workers.log &
php $SCRIPT 2>&1 >> workers.log &
php $SCRIPT 2>&1 >> workers.log &
