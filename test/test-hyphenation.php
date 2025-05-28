#!/usr/bin/env php
<?php
chdir(__DIR__);

require_once __DIR__.'/../php/hyphenation.php';

$tests = trim(file_get_contents('input-hyphenation.txt'));
$tests = explode("\n", $tests);

$good = true;
for ($i=0 ; $i<count($tests) ; ++$i) {
    $h = $tests[$i];
    $r = preg_replace('/-/u', '', $tests[$i]);
    $rv = preg_replace('/\x{00ad}/u', '-', kal_hyphenate($r));
    if ($rv !== $h) {
		$good = false;
        echo "ERROR: {$r} returned {$rv}, but should be {$h}\n";
    }
}
if ($good) {
	echo "SUCCESS\n";
}
