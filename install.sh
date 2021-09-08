#!/bin/sh
liveserver=`~/public_html/wp-content/plugins/ironpaws`
read -p "Press any key to proceed with deleting live folders"
rm -R ~/public_html/wp-content/plugins/ironpaws/css
rm -R ~/public_html/wp-content/plugins/ironpaws/includes
rm -R ~/public_html/wp-content/plugins/ironpaws/vendor
rm -R ~/public_html/wp-content/plugins/ironpaws/tests
rm -R ~/public_html/wp-content/plugins/ironpaws/img
rm -R ~/public_html/wp-content/plugins/ironpaws/tests
rm ~/public_html/wp-content/plugins/ironpaws/*

mkdir ~/public_html/wp-content/plugins/ironpaws/css
mkdir ~/public_html/wp-content/plugins/ironpaws/includes
mkdir ~/public_html/wp-content/plugins/ironpaws/vendor
mkdir ~/public_html/wp-content/plugins/ironpaws/settings
mkdir ~/public_html/wp-content/plugins/ironpaws/img
mkdir ~/public_html/wp-content/plugins/ironpaws/tests

cp *.php ~/public_html/wp-content/plugins/ironpaws/
cp *.html ~/public_html/wp-content/plugins/ironpaws/
cp -R css/* ~/public_html/wp-content/plugins/ironpaws/css/
cp -R -v includes/ ~/public_html/wp-content/plugins/ironpaws/
cp -R vendor/* ~/public_html/wp-content/plugins/ironpaws/vendor/
cp -R img/* ~/public_html/wp-content/plugins/ironpaws/img/
cp -R tests/* ~/public_html/wp-content/plugins/ironpaws/tests/
