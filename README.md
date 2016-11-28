# NetDns2 - Native PHP5 DNS Resolver and Updater by Mike Pultz #

### The main features for this package include: ###

  * Increased performance; most requests are 2-10x faster than Net\_DNS
  * Near drop-in replacement for Net\_DNS
  * Uses PHP5 style classes and exceptions
  * Support for IPv4 and IPv6, TCP and UDP sockets.
  * Includes a separate, more intuitive "Updater" class for handling dynamic update
  * Support zone signing using TSIG and SIG(0) for updates and zone transfers
  * Includes a local cache using shared memory or flat file to improve performance
  * includes many more RR's, including DNSSEC RR's.


## Installing Net\_DNS2 ##

You can require it directly via Composer: https://packagist.org/packages/orgoth/netdns2

```
composer require orgoth/netdns2
```

Or download the source above.

## Requirements ##

* PHP 5.5.0+
* The PHP INI setting `mbstring.func_overload` equals 0, 1, 4, or 5.


## Using NetDns2 ##

See the NetDns2 Website for more details - https://netdns2.com/

## Credits ##

All credits for the amazing work belongs to Mike Pultz.

## Notice ##

I have only ported the scripts to PSR-4. 