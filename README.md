TSM Monitor 1
=============
  
About
-----
  
TSM monitor is a web application written in php to help TSM administrators to quickly get reports and health status information of their TSM  (IBM Tivoli Storage Manager) servers. It generates it’s content dynamically so one can easily add or modify queries to adapt the application to one’s own needs.
  
Features:
  
* customizable queries
* dynamically generated navigation menu
* overview page with traffic light logic
* graphical timetable charts for queries with start and end time (like backups and schedules)
* multiple servers
* login protection (authentication against default tsm server)
* result caching for better performance
* Sorting – you can now sort query result tables by column dynamically, ascending and descending
* Searching – queries like “show me all backups of node xyz” are now possible with dynamically modified queries through a search field
  
Screenshots
-----------

For Screenshots, look into /screenshots  
  
Documentation
-------------
  
Requirements
  
* PHP5 or newer
* Apache 2.x or newer
* dsmadmc with all servers listed in your dsm.opt/sys. Since v0.6 you just need one SERVERNAME entry for every TSM server defined in your server.xml (Linux/Unix: dsm.sys, Windows: dsm.opt)
  
  
  
Installation
------------
  
* download the newest version of TSM monitor
* extract the package to your htdocs folder
* chown all files to your apache/www-User
* edit includes/server.xml
* make your dsmerror.log file writetable to the www user!
  
  
Configuration
-------------
  
There is only one file that needs to be edited: includes/server.xml
  
Enter here your server(s) like described below. The "defaultserver" is the server that will be displayed by default.
  
<code>
  <config>
        <serverarray>
                <defaultserver>TSMSRV1</defaultserver>
                <server>
                        <servername>TSMSRV1</servername>
                        <description>Subnet2</description>
                        <ip>172.xx.x.xxx</ip>
                        <port>1500</port>
                </server>
                <server>
                        <servername>TSMSRV2</servername>
                        <description>Subnet2</description>
                        <ip>172.xx.xxx.xx</ip>
                        <port>1500</port>
                </server>
        </serverarray>
  </config>
</code> 
  
Usage
----
  
Open your favourite browser (Firefox ;-)) and point it to http://yourserver/index.php
  
  
