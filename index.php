<?php
/*
 *  Options Start Type
 *  0 : Single Mysql Backup
 *  1 : Single Site Backup
 *  2 : Mysql and Site Backup
 */

/*
 * if you want delete old backup file you must use ->deleteDay parameter
 * if you want site backup block files , you must use ->blocklist array
 */
include "Backup.php";
$backup = new Backup("localhost","","root",""); // DB CONFİG
$backup->deleteDay = 2;
$backup->blockList = ['.DS_Store','.idea',$_SERVER['DOCUMENT_ROOT'].'/.',$_SERVER['DOCUMENT_ROOT'].'/..',];

$backup->start(2); // Start BackUp
