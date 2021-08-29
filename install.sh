#!/bin/sh
read -p "Press any key to proceed with deleting live folders"
rm -R ~/public_html/wp-content/plugins/ironpaws/css
rm -R ~/public_html/wp-content/plugins/ironpaws/includes
rm -R ~/public_html/wp-content/plugins/ironpaws/vendor
rm -R ~/public_html/wp-content/plugins/ironpaws/tests
rm ~/public_html/wp-content/plugins/ironpaws/*

mkdir ~/public_html/wp-content/plugins/ironpaws/css
mkdir ~/public_html/wp-content/plugins/ironpaws/includes
mkdir ~/public_html/wp-content/plugins/ironpaws/vendor
mkdir ~/public_html/wp-content/plugins/ironpaws/settings

cp *.php ~/public_html/wp-content/plugins/ironpaws/
cp *.html ~/public_html/wp-content/plugins/ironpaws/
cp -R css/* ~/public_html/wp-content/plugins/ironpaws/css/
cp includes/*.php ~/public_html/wp-content/plugins/ironpaws/includes/
cp includes/*.html ~/public_html/wp-content/plugins/ironpaws/includes/
cp -R vendor * ~/public_html/wp-content/plugins/ironpaws/vendor/