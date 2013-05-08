#!/bin/sh
# This script will set correct permissions for your Joomla 1.5 site under linux.
# you need to run this script as root.
# Made by: Fableman

A=`whoami`
B=1
C=root

if [ $A = "root" ]
then
echo
else
echo "ERROR: This script need root permissions (type:  su  to become root)"
exit -1
fi

if [ $B = 1 ]
then
echo "Setting Permissions.. for user: $C and user:apache"

chown nginx:nginx administrator/components
chown nginx:nginx administrator/language
chown nginx:nginx administrator/language/en-GB
chown nginx:nginx administrator/language/overrides
chown nginx:nginx administrator/manifests/files
chown nginx:nginx administrator/manifests/libraries
chown nginx:nginx administrator/manifests/packages
chown nginx:nginx administrator/modules
chown nginx:nginx administrator/templates
chown nginx:nginx components
chown nginx:nginx -R images
chown nginx:nginx images/banners
chown nginx:nginx images/sampledata
chown nginx:nginx language
chown nginx:nginx language/en-GB
chown nginx:nginx language/overrides
chown nginx:nginx libraries
chown nginx:nginx -R media
chown nginx:nginx modules
chown nginx:nginx plugins
chown nginx:nginx plugins/authentication
chown nginx:nginx plugins/content
chown nginx:nginx plugins/editors
chown nginx:nginx plugins/editors-xtd
chown nginx:nginx plugins/extension
chown nginx:nginx plugins/search
chown nginx:nginx plugins/system
chown nginx:nginx plugins/user
chown nginx:nginx -R tmp
chown nginx:nginx logs
chown nginx:nginx templates
chown nginx:nginx -R cache
chown nginx:nginx administrator/cache

chmod 770 administrator/components
chmod 770 administrator/language
chmod 770 administrator/language/en-GB
chmod 770 administrator/language/overrides
chmod 770 administrator/manifests/files
chmod 770 administrator/manifests/libraries
chmod 770 administrator/manifests/packages
chmod 770 administrator/modules
chmod 770 administrator/templates
chmod 770 components
chmod 770 images
chmod 770 images/banners
chmod 770 images/sampledata
chmod 770 language
chmod 770 language/en-GB
chmod 770 language/overrides
chmod 770 libraries
chmod 770 media
chmod 770 modules
chmod 770 plugins
chmod 770 plugins/authentication
chmod 770 plugins/content
chmod 770 plugins/editors
chmod 770 plugins/editors-xtd
chmod 770 plugins/extension
chmod 770 plugins/search
chmod 770 plugins/system
chmod 770 plugins/user
chmod 770 tmp
chmod 770 logs
chmod 770 templates
chmod 770 cache
chmod 770 administrator/cache
echo "DONE! Now login into your Joomla site as admin and goto Help / System Info  and check that all permission are Writable."
echo "(type: exit    to go back to normal user)"

else
  echo "ERROR: THIS SCRIPT MUST BE RUN AT THE INSTALLATION OF JOOMLA 1.5 "
  echo "ERROR: Your not at the start possition (root) of joompla installation. (ex. cd /home/yourusername/public_html)"
fi
