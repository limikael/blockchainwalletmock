<?php

	$fn=__DIR__."/callback.log";
	$res=file_put_contents($fn,json_encode($_REQUEST));
