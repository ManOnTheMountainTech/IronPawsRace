#!/bin/sh
liveserver='/home/bryan/public_html/wp-content/plugins/ironpaws'
read -p "Press any key to proceed with deleting live folders"
rm -R -f /home/bryan/public_html/wp-content/plugins/ironpaws/css
rm -R -f /home/bryan/public_html/wp-content/plugins/ironpaws/includes
rm -R -f /home/bryan/public_html/wp-content/plugins/ironpaws/vendor
rm -R -f /home/bryan/public_html/wp-content/plugins/ironpaws/tests
rm -R -f /home/bryan/public_html/wp-content/plugins/ironpaws/img
rm -R -f /home/bryan/public_html/wp-content/plugins/ironpaws/tests
rm -f /home/bryan/public_html/wp-content/plugins/ironpaws/*

mkdir /home/bryan/public_html/wp-content/plugins/ironpaws/css
mkdir /home/bryan/public_html/wp-content/plugins/ironpaws/includes
mkdir /home/bryan/public_html/wp-content/plugins/ironpaws/vendor
mkdir /home/bryan/public_html/wp-content/plugins/ironpaws/settings
mkdir /home/bryan/public_html/wp-content/plugins/ironpaws/img
mkdir /home/bryan/public_html/wp-content/plugins/ironpaws/tests

cp *.php /home/bryan/public_html/wp-content/plugins/ironpaws/
cp *.html /home/bryan/public_html/wp-content/plugins/ironpaws/
cp -R css/ /home/bryan/public_html/wp-content/plugins/ironpaws/
cp -R includes/ /home/bryan/public_html/wp-content/plugins/ironpaws/
cp -R vendor/ /home/bryan/public_html/wp-content/plugins/ironpaws/
cp -R img/ /home/bryan/public_html/wp-content/plugins/ironpaws/
cp -R tests/ /home/bryan/public_html/wp-content/plugins/ironpaws/
