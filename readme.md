This project is the result of a skill demo assignment from Leviy for Patrick Morpey in November 2020.

I've decided to base it on my own collection of reusable class files, those I use for personal projects.

The directory `Project` contains files unique to this project; `Shared` contains the resuable class files. Not every 
one of those files has been optimized and tested for production usage, as they're meant for personal projects :-).  

#Installation
Unpack all files into a directory.

Rename Settings/LocalSettings.php.sample to Settings/LocalSettings.php

#Usage
Execute `php bin/determine-schedule.php`. The script will write a CSV file in the directory `output-files` and output whether it was succesfull or not. 
 
