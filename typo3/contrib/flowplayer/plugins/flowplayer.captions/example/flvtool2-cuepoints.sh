#!/bin/bash
#
# /usr/local/bin/cuepoints
#
# Generate an XML file used by FLVTool2 to add cuepoints to a flash file
# The XML file adds a cuepoint every ten seconds, to line up with timestamps
#
# Syntax: cuepoints [<seconds>] [<filename>]
#         cuepoints 1800 cuepoints-1800.xml
#         cuepoints 7200
#         cuepoints
#
# Options for start time and interval could be added
#
# -----------------------------------------------------------------------------

# Add a help screen
if [ "$1" = "-h" -o "$1" = "--help" -o "$1" = "help" ]
 then echo -e "\ncuepoints [<seconds>] [<filename>]"
  echo -e "\tThe cuepoints script creates an XML file used by FLVTool2 to add cuepoints to a flash file"
  echo -e "\nSyntax:"
  echo -e "\tcuepoints \t\t(defaults to 3600 seconds, or one hour, written to cuepoints.xml)"
  echo -e "\tcuepoints 1800 \t\t(create cuepoints for a half-hour file)"
  echo -e "\tcuepoints 1800 cuepoints-1800.xml\t(specify a filename)\n"
   exit
fi

echo -e "\nCreating cuepoints.xml, used by FLVTool2 to add cuepoints to a flash file \
(see \"cuepoints help\" for syntax and options)\n"

# Check for length
if [ "$1" = "" ]
 then LENGTH="3600"
 else LENGTH="$1"
fi

# Check for filename
if [ "$2" = "" ]
 then FIL="cuepoints.xml"
 else FIL="$2"
fi
 
# Write the header
echo "<?xml version=\"1.0\"?>" > $FIL
echo "<tags>" >> $FIL
echo "  <!-- navigation cue points -->" >> $FIL
 
# Write the body
for N in $(seq 0 10 $LENGTH)
do
 NAME="$( echo $(date -d "+$N seconds"\ 00:00:00 +%H:%M:%S) )"
 echo "  <metatag event=\"onCuePoint\">" >> $FIL
 echo "    <name>"$NAME"</name>" >> $FIL
 echo "    <timestamp>"$N"000</timestamp>" >> $FIL
 echo "    <type>navigation</type>" >> $FIL
 echo "  </metatag>" >> $FIL
done
 
# Write the footer
echo "</tags>" >> $FIL
  
echo -e "An XML file specifying $LENGTH timestamps has been written to $FIL\n"

echo -e "To create a flash file with these cuepoints, use \"flvtool2 -AUPt cuepoints.xml input.flv output.flv\"\n"

