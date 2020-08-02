#!/bin/bash
# by arjen 2019-04-14


# max aggregate zone file age in minutes (7 * 1440 = 10800 mins)
FILE_MAXAGE=10800


# params
# $1    "ok" | "bad"
# $2    "co"	(ISO 2-letter country code)
# $3    "ipv4" | "ipv6"
add_zone () {
  if [ $3 == "ipv6" ]
  then
    BLOCKNAME="geoblock6-$1"
    URL="http://www.ipdeny.com/ipv6/ipaddresses/aggregated/$2-aggregated.zone"
    FNAME="inet6.$2.zone"
  else
    BLOCKNAME="geoblock4-$1"
    URL="http://www.ipdeny.com/ipblocks/data/aggregated/$2-aggregated.zone"
    FNAME="inet.$2.zone"
  fi

  if test $(find $FNAME -mmin +$FILE_MAXAGE)
  then
    rm $FNAME
  fi

  if [ ! -r $FNAME ]
  then
    wget -O $FNAME $URL
  fi

  if [ ! -s $FNAME ]
  then
    echo "File $FNAME is empty!"
  else
    echo "Reading $FNAME         "
    while read ipblock; do
      echo -en "$ipblock        \r"
      sudo ipset -exist add $BLOCKNAME $ipblock
    done < $FNAME
    echo "===                                                           "
  fi
}


# set timeout to 30 days (30 * 86400 seconds = 2592000)
TIMEOUT=2592000

##### not needed
#sudo ipset destroy geoblock4-ok
#sudo ipset destroy geoblock4-bad
#sudo ipset destroy geoblock6-ok
#sudo ipset destroy geoblock6-bad

for COUNTRY in {nl,de,gb}
do
	sudo ipset -exist create georoute4-$COUNTRY hash:net family inet timeout $TIMEOUT
	while read ipblock; do
		sudo ipset -exist add georoute4-$COUNTRY $ipblock
	done <inet.$COUNTRY.zone
done

##### needed for country blocks
#sudo ipset -exist create geoblock4-ok  hash:net family inet  timeout $TIMEOUT
#sudo ipset -exist create geoblock4-bad hash:net family inet  timeout $TIMEOUT
#sudo ipset -exist create geoblock6-ok  hash:net family inet6 timeout $TIMEOUT
#sudo ipset -exist create geoblock6-bad hash:net family inet6 timeout $TIMEOUT
#
#
#for COUNTRY in {au,nz,nl,ch,de,fr,gb}
#do
#  add_zone "ok" $COUNTRY "ipv4"
#  add_zone "ok" $COUNTRY "ipv6"
#done
#
#
#for COUNTRY in {cn,hk,ru}
#do
#  add_zone "bad" $COUNTRY "ipv4"
#  add_zone "bad" $COUNTRY "ipv6"
#done


