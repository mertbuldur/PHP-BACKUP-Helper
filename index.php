<?php
// 0: Single Mysql , 1 : Single Site , 2 : Full Back Up
include "Backup.php";
$backup = new Backup("localhost","","root","");
$backup->deleteDay = 2;
$backup->blockList = ['.DS_Store','.idea',$_SERVER['DOCUMENT_ROOT'].'/.',$_SERVER['DOCUMENT_ROOT'].'/..',];
$backup->start(2);
