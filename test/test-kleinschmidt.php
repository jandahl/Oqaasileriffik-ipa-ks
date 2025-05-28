#!/usr/bin/env php
<?php
chdir(__DIR__);

require_once __DIR__.'/../php/kleinschmidt.php';

$inputs = trim(file_get_contents('input-kleinschmidt.txt'));
$inputs = explode("\n", $inputs);

$outputs = [];
for ($i=0 ; $i<count($inputs) ; ++$i) {
	$o = do_kal_kleinschmidt($inputs[$i]);
	$o = preg_replace('/\s+/us', "\n", $o);
	$o = preg_replace('/^\s+/us', '', preg_replace('/\s+$/us', '', $o));
	$outputs[$i] = $o;
}

file_put_contents('output-kleinschmidt.txt', implode("\n\n", $outputs));
$out = shell_exec('diff expected-kleinschmidt.txt output-kleinschmidt.txt');
if ($out) {
	echo "FAILURE:\n$out";
}
else {
	echo "SUCCESS\n";
}
