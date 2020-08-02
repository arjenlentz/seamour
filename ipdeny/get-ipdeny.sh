#!/bin/bash

for COUNTRY in au nz nl gb cn hk ru
do
	wget -O inet.${COUNTRY}.zone http://www.ipdeny.com/ipblocks/data/aggregated/${COUNTRY}-aggregated.zone
	wget -O inet6.${COUNTRY}.zone http://www.ipdeny.com/ipv6/ipaddresses/aggregated/${COUNTRY}-aggregated.zone
done



#for IP in $(wget -O – http://www.ipdeny.com/ipblocks/data/aggregated/{au,nz,nl,gb}-aggregated.zone)
#do
# list all IPv4 ranges for these countries
#echo ipset add geoblock4 $IP
#sudo ipset add geoblock4 $IP
#done

#for IP in $(wget -O – http://www.ipdeny.com/ipv6/ipaddresses/aggregated/{au,nz,nl,gb}-aggregated.zone)
#do
## list all IPv6 ranges for these countries
#sudo ipset add geoblock6 $IP
#done

# (/etc/rc.local)
# sudo ipset save | sudo tee /etc/ipset.up.rules
# sudo iptables -I INPUT -m set --match-set geoblock src -j DROP
# sudo iptables -I INPUT -m set --set !geoblock src -j DROP

