<?php

	$fn=__DIR__."/../data/callback.log";
	$res=file_put_contents($fn,json_encode($_REQUEST)."\n",FILE_APPEND);
