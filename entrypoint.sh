#!/bin/bash
sed -i "s/80/${PORT:-10000}/g" /etc/apache2/ports.conf /etc/apache2/sites-enabled/000-default.conf
exec apache2-foreground