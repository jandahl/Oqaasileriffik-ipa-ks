<?php

$GLOBALS['-old-words'] = [
	'b' => ['baaja', 'baalia', 'baaliar', 'bajeri', 'biibili', 'biili', 'biiler', 'bussi'],
	'd' => ['diaavulu', 'decembari'],
	'f' => ['farisiiari', 'februaari', 'feeria', 'feeriar', 'freer'],
	'g' => ['gassi', 'guuti'],
	'h' => ['hiisti', 'horaa', 'horaartor', 'huaa', 'huaartor'],
	'j' => ['januaari', 'joorli', 'joorlisior', 'jorngoq', 'juuli', 'juulli', 'juumooq', 'juuni', 'juuti'],
	'l' => ['laaja', 'lakker', 'lakki', 'lal\'laaq', 'lappi', 'liimmer', 'liimmi'],
	'r' => ['raaja', 'rinngi', 'rommi', 'russeq', 'ruua', 'ruujori', 'ruusa', 'ruusaar'],
	'v' => ['viinnequt', 'viinni'],
	];

function kal_detect_from($token) {
	// While we potentially could handle foreign words with characters like İ (U+0130) that have Greenlandic endings, they're so rare that we won't bother
	$token_lc = mb_strtolower($token);
	if (mb_strlen($token) != mb_strlen($token_lc)) {
		return mb_strlen($token);
	}

	$token = $token_lc;
	$from = 0;

	$first = mb_substr($token, 0, 1);
	if (array_key_exists($first, $GLOBALS['-old-words'])) {
		for ($i=0 ; $i<count($GLOBALS['-old-words'][$first]) ; ++$i) {
			if ($token == $GLOBALS['-old-words'][$first][$i]) {
				return 0;
			}
		}
		$from = 1;
	}

	if (!preg_match('/[aeikmnopqstu]/u', $first)) {
		$from = max($from, 1);
	}

	if (preg_match('/^.[bcdwxyzæøå]/u', $token)) {
		$from = max($from, 2);
	}

	// ToDo: 'ai' anywhere but the end signifies non-kal
	if (preg_match('/^.ai/u', $token)) {
		$from = max($from, 3);
	}

	while (preg_match('/[eo]+[^eorq]/u', $token, $m, PREG_OFFSET_CAPTURE, $from)) {
		$from = max($from, $m[1][1]+1);
	}

	$last = mb_substr($token, -1);
	if (!preg_match('/[aikpqtu]/u', $last)) {
		$from = max($from, mb_strlen($token));
	}

	if (preg_match('/[^aefgijklmnopqrstuvŋ][aefgijklmnopqrstuvŋ]+$/u', $token, $m, PREG_OFFSET_CAPTURE, $from)) {
		$from = max($from, $m[1][1]+1);
	}

	if (preg_match_all('/([qwrtpsdfghjklzxcvbnmŋ])([qwrtpsdfghjklzxcvbnmŋ])/u', $token, $ms, PREG_SET_ORDER|PREG_OFFSET_CAPTURE, $from)) {
		foreach ($ms as $rv) {
			if ($rv[1][0] === 'r') {
				continue;
			}
			if ($rv[1][0] == 'n' && $rv[2][0] == 'g') {
				continue;
			}
			if ($rv[1][0] == 't' && $rv[2][0] == 's') {
				continue;
			}
			//echo var_export($rv, true), "\n";
			if ($rv[1][0] !== $rv[2][0]) {
				$from = max($from, $rv[1][1]+2);
			}
		}
	}

	return $from;
}

function kal_hyphenate_word($token) {
	if (!preg_match('/^[a-zA-ZæøåÆØÅŋ]+$/ui', $token)) {
		return $token;
	}
	$token = preg_replace('/nng/u', "\u{e000}\u{e000}", $token);
	$token = preg_replace('/ng/u', "\u{e000}", $token);

	$C = '/[bcdfghjklmnŋ\x{e000}pqrstvwxzBCDFGHJKLMNPQRSTVWXZ]/iu';
	$V = '/[aeiouyæøåAEIOYÆØÅ]/iu';

	$i = 0;
	$split = '';
	for ($e=mb_strlen($token)-1 ; $i<$e ; ++$i) {
		$t0 = mb_substr($token, $i, 1);
		$t1 = mb_substr($token, $i+1, 1);
		$t2 = mb_substr($token, $i+2, 1);
		$t3 = mb_substr($token, $i+3, 1);

		$split .= $t0;
		if (preg_match($V, $t0) && preg_match($C, $t1) && preg_match($V, $t2)) {
			$split .= "\u{00ad}"; // U+00AD is Soft Hyphen
		}
		else if (preg_match($V, $t0) && preg_match($C, $t1) && preg_match($C, $t2) && preg_match($V, $t3)) {
			++$i;
			$split .= $t1;
			$split .= "\u{00ad}";
		}
		else if (mb_strtolower($t0) !== mb_strtolower($t1) && preg_match($V, $t0) && preg_match($V, $t1)) {
			$split .= "\u{00ad}";
		}
	}
	$split .= mb_substr($token, $i);

	$split = preg_replace("/\x{e000}\x{00ad}\x{e000}/u", "n\u{00ad}ng", $split);
	$split = preg_replace("/\x{e000}\x{e000}/u", 'nng', $split);
	$split = preg_replace("/\x{e000}/u", 'ng', $split);

	$split = preg_replace("/a\x{00ad}i$/u", 'ai', $split);

	return $split;
}

function kal_hyphenate_words($txt) {
	$ws = preg_split('/(\s+)/u', $txt, -1, PREG_SPLIT_DELIM_CAPTURE);
	for ($i=0 ; $i<count($ws) ; ++$i) {
		$ws[$i] = kal_hyphenate($ws[$i]);
	}
	return implode('', $ws);
}

function kal_hyphenate($text) {
	$sents = preg_split('/([.:!?]\s+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
	$hyphens = '';

	for ($ln=0 ; $ln<count($sents) ; ++$ln) {
		$tokens = preg_split('/([^\wŋæøå]+)/ui', $sents[$ln], -1, PREG_SPLIT_DELIM_CAPTURE);

		for ($i=0 ; $i<count($tokens) ; ++$i) {
			$token = $tokens[$i];
			$from = kal_detect_from($token);
			if (!preg_match('/\w+/u', $token) || $from == 0) {
				$hyphens .= kal_hyphenate_word($token);
				continue;
			}

			$hyphens .= mb_substr($token, 0, $from) . kal_hyphenate_word(mb_substr($token, $from));
		}
	}

	return $hyphens;
}
