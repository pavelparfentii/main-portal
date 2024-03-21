#!/bin/bash

#chown -R www-data:www-data /var/www/global_portal/
find /var/www/global_portal/storage -type d -exec chmod 777 -R {} \;
find /var/www/test_portal/global-admin-portal/storage -type f -exec chmod 777 -R {} \;
