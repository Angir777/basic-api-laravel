#!/bin/sh
sudo chown -R $USER:www-data $1
sudo find $1 -type f -exec chmod 644 {} \;
sudo find $1 -type d -exec chmod 755 {} \;
echo -n 'Files and dir permissions set to $USER:www-data \n';

cd $1
sudo chgrp -R www-data storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache
echo -n 'Permissions set for bootstrap/cache \n';
sudo chown -R dro:www-data storage/logs
echo -n 'Permissions set for storage/logs \n';

