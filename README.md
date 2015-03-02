Web based interface for controlling the Raspberry Pi Camera, includes motion detection, time lapse, and image and video recording.

All information on this project can be found here: http://www.raspberrypi.org/forums/viewtopic.php?f=43&t=63276

The wiki page can be found here:

http://elinux.org/RPi-Cam-Web-Interface

Modifications by RJ Tidey to preview page
Instead of a list of the captured filenames they are parsed into a table
making it a little clearer and allowing more rapid delete of individual files.
Images recorded are displayed as thumbnails.

Videos recorded after motion detection can also have a thumbnail of their first
capture frame generated by motion.

To do this requires a couple of config lines in motion.conf to be edited.
output_normal first.

target_dir /var/www/media

jpeg_filename vthumb_%Y%m%d_%H%M%S

These allow motion to put a thumbnail into the media folder when triggered.
The new preview.php associates these with the corresponding recording.

Thumbnails are also generated for manually recorded images
and videos using ffmpeg. It can give a delay when going into the list if new material needs
to be thumbnailed, but after that it is faster.

Each row has an explicit preview and delete button and select checkboxes.
Select all, Select None and Delete selected, and Get Zip functions added.

Preview and thumbnail sizes can be changed per browser (cookies).

Tech changed from GET to POST and download moved into preview.php

20th Feb 2015
Change style of preview to a group of thumbnails.
Styling of preview improved and preview.css added to css folder

21st Feb 2015
Add video/image indicator back into file captures

23rd Feb 2015
Initial version to allow setting motion.conf from web interface

24th Feb 2015
Added a thumbnail orphan check in preview.phpto make sure there are no spurious thumbnails left over.
Motion.php detects and warns if motion not running.
Added Backup and restore buttons. These save to a server side json file.

25th Feb 2015
Installer script sync'd to master
Bug in motion settings restore corrected

1st March
Fixed broken download

2nd March
Added Schedule.php which provides a web page to set automation settings and a daemon to execute them.
To use the motion commands for start and end need to be 0 and 1 sent to schedule FIFOIN (/var/www/FIFO1)
Schedule will then send its configured commands to FIFOOUT (/var/www/FIFO)
Schedule needs to be started once on its settings page or can be arranged to autostart by adding a php schedule.php
command to boot.