--------------------------------------------------------------------------------

Required Packages

sqlite (to check type which sqlite3, to install either go through Synaptec
Package Manager or type sudo apt-get install sqlite3)

php5-cli (to check: which php5, to install: sudo apt-get install php5-cli)

php5-sqlite (to install sudo apt-get install php5-sqlite)

apache server (to install sudo apt-get install apache2)

Steps

Run the test script and make sure it prints something out:
php5 test.php

Create a link in your public_html folder to the folder that contains PHPscripts.

chmod 777 the database directory and the contents (probably can limit this to
specific users or fix the permissions some other way).

Restart Apache to make sure it picked up the php changes
(sudo apache2ctl restart).

Open the browser and navigate to http://your-web-server/testsrv/SonosAPI.php.
If you see a message "Error: this page is used exclusively by Sonos products." 
then you're all set and ready to set up a service.

--------------------------------------------------------------------------------

Troubleshooting

If the browser tries to download SonosAPI.php, try the following steps:

Check if php5.conf and php5.load files are present in /etc/apache2/mods-enabled.
If not, follow the steps below.

Download php5 modes for Apache by executing
sudo apt-get install libapache2-mod-php5

cd to /etc/apache2/mods-available and open php5.conf in any editor (note:
the files in mods-enabled are just symlinks to the files in mods-available).

Comment out the following lines:
   <IfModule mod_userdir.c>
       <Directory /home/*/public_html>
           php_admin_value engine Off
       </Directory>
   </IfModule>

Restart Apache server to pick up changes (sudo apache2ctl restart).

Note: I was using Internet Explorer 8 and had to close and re-open the browser
window to get it to stop trying to download the .php file (TadC)

Note: Firefox on Ubuntu 10.04 - same as above. Had to restart browser.

--------------------------------------------------------------------------------

This is yet another sample app.

The main code is in SonosAPI.php, and the rest is hiding in lib/*.php
(and Sonos.wsdl).

The primary difference between this and the full MusicBrainz sample is that
this is entirely self contained.  There is no web backend to hit, no rate
limiting etc.

The metadata all came from the public domain portion of the MusicBrainz
database, the audio came from Chuck Berry (Johnny B Goode has been released
into the public domain), and the album art is a mix of the MusicBrainz logo and
some stuff I found lying around.

There is still some code that is really ugly and needs to be refactored.  The
guts of the SimpleBackend class are a little ugly, and there is still too much
code duplication in SonosAPI.php for my taste.  At least the localization and
database init have been moved out to other PHP files.

Oh, and running "php test.php" will perform a set of backend queries and
display the results.  I hesitate to call these "tests" since they require
visual inspection, but they are better than nothing.

At this point the following work:

1) Search
2) Staff favorites (subset of artists that we like)
3) Full library browse
4) Album art
5) Audio playback (all get the same song)
6) Ratings

Real soon now the following will get added:

1) Powerscroll for full library browse (may do it in real time, we'll see)
2) Real logins and session ids (hacked for now)
3) USer favorites
4) Programmed radio w/ ads
