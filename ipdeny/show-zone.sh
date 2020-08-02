#!/bin/bash

for f in inet.*.zone
do
  echo === Reading $f

  while read name; do
    echo $name
  done < $f
done
