This is mostly a collection of the scripts and code from my localhost and SMF development environment setup.  To explain it properly, I will attempt detail out the setup.  You are welcome to use the setup or any parts of the code.  Please just give credit where credit is due.  This has mostly been a work on progress on my local environment for a nice and easy setup.

File Structure 
/srv/software/nginx - My local built from source nginx install
/srv/software/php/v5 - My local built from source PHP+FPM 5.3 install
/srv/software/php/v54 - Same as my above php install but PHP 5.4
/srv/repos - All the source code I work with goes here and locally organized
	- /srv/repos/mine - All my repositories.
	- /srv/repos/svn/smf - SMFs current repository (at least until its git)
/srv/logs - All the logs go here.
/srv/data - Most of the databases such as mysql and postgresql


Modifying my /etc/hosts file I resolved some urls to my localhost (127.0.0.1) address.  Some of these urls are:
smf.test
dev.test
svn.test - deprecated in my setup for preparations for smf to go to git.
smfMaster.test - This works directly off my svn repository using some tricks not modify files.
postgresql.smfMaster.test - Again using my svn repo this loads the postgresql version
sqlite.smfMaster - Repeating above but for SQLite.


As for the rest of the setup.  I simply have Nginx running on port 80, and 81.  When Nginx detects port 81 it is serving requests to php 5.4 fpm, otherwise it serves them to php 5.3 requests.  That is fairly simple.

On my smfMaster.test sites this is a bit more complicated.  I overload some normal settings.  Mostly being I do a uto prepend file on all php requests to include a nginx_handler.php.  That file does the magic of loading the correct index or upgrade handler.  These handlers modify some calls so that way we don't need to include a Settings.php directly in our repo and accidentally commit it or something.  This also allows for some after affects to occur such as a true page creation time and a quick theme changer.

My dev.test runs generic things, the assets and test_code folders.  This also happens to contain my phpmyadmin and other tools like that.

My smf.test contains as you guessed it, SMF installs.  From its index file contains a action which lets me pull from any svn tag, branch or the trunk.  I will need to rewrite this for Git, but it sounds like work.  My svn checkout is the entire SMF svn repo and so I have all tags and branches.  Git is a bit different where I would need to add that from the remote repo as a tag, switch to that tag.  Doesn't sound as fun to setup.

dev.test and smf.test both do a pre include as well to auto pull in a site handler that handles the theme stuff and other things for my localhost.  A bit fancy for a localhost setup, but it makes it very fast to add new code to my setup.