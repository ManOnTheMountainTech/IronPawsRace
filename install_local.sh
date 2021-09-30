#!/bin/sh
ironpaws_dir='/usr/share/wordpress/wp-content/plugins/ironpaws'
ironpaws_src_dir="~/Documents/ironpaws"
read -p "Press any key to proceed with deleting live folders"
rm -R "$ironpaws_dir/css"
rm -R "$ironpaws_dir/includes"
rm -R "$ironpaws_dir/vendor"
rm -R "$ironpaws_dir/tests"
rm -R "$ironpaws_dir/img"
rm -R "$ironpaws_dir/tests"
rm /*

mkdir /css
mkdir /includes
mkdir /vendor
mkdir /settings
mkdir /img
mkdir /tests

cp *.php $ironpaws_src_dir
cp *.html $ironpaws_src_dir
cp -R css/* $ironpaws_src_dir/css/
cp -R -v includes/ $ironpaws_src_dir/includes/
cp -R vendor/* $ironpaws_src_dir/vendor/
cp -R img/* $ironpaws_src_dir/img/
cp -R tests/* $ironpaws_src_dir/tests/
