#!/bin/sh

if [ ! -d "/var/www1" ]; then
   mkdir /var/www1
fi

exec "$@"
