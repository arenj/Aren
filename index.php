<?php
use Aren\Core\Core;

define('TIME_START', microtime(true));
define('MEMORY_START', memory_get_usage());
	
include "./Aren/Core/Core.class.php";

Core::bootstrap();
