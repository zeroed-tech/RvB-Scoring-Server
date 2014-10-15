<?php
session_name("RvB");
session_start();



require('db-config.inc.php');
require('db-functions.inc.php');
require('shared-functions.inc.php');
require('custom-classes.inc.php');

$CONFIG = getConfig();
