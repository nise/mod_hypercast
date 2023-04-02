docker exec moodle rm -rfv /var/www/mod/hypercast/*

tar -xvf /home/develop/tmp/project.tar.gz -C /home/develop/hypercast/
rm /home/develop/tmp/project.tar.gz
cp -r /home/develop/demo_data/* /home/develop/hypercast/assets/

docker exec moodle composer install --working-dir=/var/www/mod/hypercast --no-dev