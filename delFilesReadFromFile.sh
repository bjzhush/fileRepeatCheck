#!/bin/bash
#input a filename ,read all lines from the file ,and delete each line as a new filename
echo "You'd better use sudo and check it before execute it'"
echo "!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!"
echo "Enter the file name:" 
read filename
echo "You'll delete all files recorded in" $filename
cat $filename
xargs rm <$filename
