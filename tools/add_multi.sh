#!/bin/bash
script="../tests/add.php"
cd $(dirname $0)

php $script 2>&1 >> add_multi.log &
php $script 2>&1 >> add_multi.log &
php $script 2>&1 >> add_multi.log &
php $script 2>&1 >> add_multi.log &
php $script 2>&1 >> add_multi.log &


php $script 2>&1 >> add_multi.log &
php $script 2>&1 >> add_multi.log &
php $script 2>&1 >> add_multi.log &
php $script 2>&1 >> add_multi.log &
php $script 2>&1 >> add_multi.log &
