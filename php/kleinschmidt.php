<?php

$GLOBALS['-old-words'] = [
	'a' => ['augustuse', 'aprîle'],
	'b' => ['bâja', 'bâlia', 'bâliar', 'bajere', 'binsîna', 'bîbile', 'bîle', 'bîler', 'bilíte', 'børnehave'],
	'd' => ['danskeĸ', 'diâvulo', 'decembare'],
	'f' => ['farisîare', 'februâre', 'fêria', 'fêriar', 'filmer', 'filmeriar', 'filme', 'frêr'],
	'g' => ['gáse', 'gigte', 'gipse', 'gomôrnêr', 'gymnasia', 'guitare', '/^gût/u'],
	'h' => ['hîste', 'horâ', 'horârtor', 'huâ', 'huârtor'],
	'j' => ['januâre', 'jôrle', 'jôrlisior', 'jorngoĸ', 'jûle', 'jûlle', 'jûmôĸ', 'jûne', 'jûte'],
	'k' => ['kláse', 'kûlte'],
	'l' => ['lâja', 'láker', 'láke', 'lal\'lâq', 'lápe', 'lastbîle', 'linia', 'lĩmer', 'lĩme'],
	'm' => ['ministere', 'ministeriuneĸ'],
	'n' => ['néte', 'novembare'],
	'p' => ['politî', 'politikere', 'politíke'],
	'r' => ['râja', 'râtio', 'rínge', 'róme', 'rúseĸ', 'rûa', 'rûjore', 'rûsa', 'rûsâr'],
	's' => ['septembare', 'sikunte', 'silveĸ', 'skû'],
	't' => ['taxa', 'taxar'],
	'v' => ['vĩneĸut', 'vĩni'],
	];

$GLOBALS['-blacklist'] = [
	'd' => ['diskobugt'],
	];

$GLOBALS['-specials'] = [
	'pôrske' => 'poorski',
	];

function is_upper($ch) {
	return ($ch === mb_strtoupper($ch) && $ch !== mb_strtolower($ch));
}

function mb_ucfirst($w) {
	return mb_strtoupper(mb_substr($w, 0, 1)).mb_substr($w, 1);
}

function klein_kal_from($otoken) {
	$token = mb_strtolower($otoken);
	$from = 0;

	if (array_key_exists($token, $GLOBALS['-specials'])) {
		return 0;
	}

	$first = mb_substr($token, 0, 1);
	if (!empty($GLOBALS['-old-words'][$first])) {
		foreach ($GLOBALS['-old-words'][$first] as $m) {
			if ($token === $m) {
				return 0;
			}
			if ($m[0] === '/' && preg_match($m, $token)) {
				return 0;
			}
		}
	}

	if (!empty($GLOBALS['-blacklist'][$first])) {
		foreach ($GLOBALS['-blacklist'][$first] as $m) {
			if (strpos($token, $m) === 0) {
				$from = strlen($m);
			}
		}
	}

	if (!preg_match('/[aáâãeêiíîĩkmnoôpĸstuúûũ]/u', $first)) {
		$from = max($from, 1);
	}

	if (preg_match('/^.[bcwxyzæøå]/u', $token)) {
		$from = max($from, 2);
	}

	// Detect leading acronyms
	if (mb_strtoupper(mb_substr($otoken, 0, 1)) === mb_substr($otoken, 0, 1)) {
		$i = 0;
		for ( ; $i<mb_strlen($otoken) ; ++$i) {
			$uc = mb_strtoupper(mb_substr($otoken, $i, $i+1));
			$lc = mb_strtolower(mb_substr($otoken, $i, $i+1));
			// If it is an uncased character such as ', stop here
			if ($uc === $lc || $uc !== mb_substr($otoken, $i, $i+1)) {
				break;
			}
		}
		if ($i > 1) {
			$from = max($from, $i);
		}
	}

	// Allows mevĸoĸ and tovĸit
	if (preg_match('/.+[eêoô]+([^vrqĸ])/u', $token, $m, PREG_OFFSET_CAPTURE)) {
		//echo var_export($m, true), "\n";
		$from = max($from, $m[1][1]+1);
	}

	if (!preg_match('/[aáâãeêkoôpĸtuúûũ]|ai$/u', $token)) {
		return strlen($token);
	}

	if (preg_match_all('/([qwrtpsdfghjkĸlzxcvbnŋm])([qwrtpsdfghjkĸlzxcvbnŋm])/u', $token, $ms, PREG_SET_ORDER|PREG_OFFSET_CAPTURE, $from)) {
		foreach ($ms as $rv) {
			if ($rv[1][0] === 'r') {
				continue;
			}
			if (preg_match('/^(gdl|gf|gp|gs|gss|gt|gk|ng|ngm|ngn|rĸ|tdl|ts|vdl|vf|vg|vk|vĸ|vn|vm|vs|vt)/u', substr($token, $rv[0][1]))) {
				continue;
			}
			//echo var_export($rv, true), "\n";
			if ($rv[1][0] !== $rv[2][0]) {
				$from = max($from, $rv[1][1]+2);
			}
		}
	}

	//echo "$token: $from\n";
	return $from;
}

function kal_klein2new($token) {
	if (!preg_match("/^[a-zæøåĸâáãêíîĩôúûũ']+$/iu", $token)) {
		return $token;
	}

	if (array_key_exists($token, $GLOBALS['-specials'])) {
		return $GLOBALS['-specials'][$token];
	}

	$token = preg_replace('/ai$/u', "\xee\x80\x80", $token);
	$token = preg_replace('/ts/u', "\xee\x80\x81", $token);
	$token = preg_replace('/ê$/u', "\xee\x80\x83", $token);

	$token = preg_replace('/^suja/u', 'sia', $token);
	$token = preg_replace('/^sujo/u', 'sio', $token);
	$token = preg_replace('/^suju/u', 'siu', $token);
	$token = preg_replace('/^sujú/u', "siu\xee\x80\x82", $token);

	$token = preg_replace("/k'/u", 'q', $token);
	$token = preg_replace('/dl/u', 'l', $token);
	$token = preg_replace('/rvng/u', 'rŋ', $token);
	$token = preg_replace('/ng/u', 'ŋ', $token);
	$token = preg_replace('/ĸ/u', 'q', $token);
	$token = preg_replace('/ss/u', 's', $token);
	$token = preg_replace('/áu/u', "aa\xee\x80\x82", $token);
	$token = preg_replace('/ái/u', "aa\xee\x80\x82", $token);
	$token = preg_replace('/â/u', 'aa', $token);
	$token = preg_replace('/á/u', "a\xee\x80\x82", $token);
	$token = preg_replace('/ã/u', "aa\xee\x80\x82", $token);
	$token = preg_replace('/ê/u', 'ee', $token);
	$token = preg_replace('/í/u', "i\xee\x80\x82", $token);
	$token = preg_replace('/î/u', 'ii', $token);
	$token = preg_replace('/ĩ/u', "ii\xee\x80\x82", $token);
	$token = preg_replace('/ô/u', 'oo', $token);
	$token = preg_replace('/ú/u', "u\xee\x80\x82", $token);
	$token = preg_replace('/û/u', 'uu', $token);
	$token = preg_replace('/ũ/u', "uu\xee\x80\x82", $token);

	$token = preg_replace('/aia/u', 'aaja', $token);
	$token = preg_replace('/aua/u', 'aava', $token);
	$token = preg_replace('/aiu/u', 'aaju', $token);
	$token = preg_replace('/aio/u', 'aajo', $token);
	$token = preg_replace('/ae/u', 'aa', $token);
	$token = preg_replace('/ai/u', 'aa', $token);
	$token = preg_replace('/ao/u', 'aa', $token);
	$token = preg_replace('/au/u', 'aa', $token);
	$token = preg_replace('/[bcdfghjklmnŋpqstvwxz\x{e002}]([fgkĸlmnŋpqrst\x{e002}])/iu', '$1$1', $token);

	$token = preg_replace('/e$/u', 'i', $token);
	$token = preg_replace('/o$/u', 'u', $token);
	$token = preg_replace('/ŋŋ/u', 'nng', $token);
	$token = preg_replace('/ŋ/u', 'ng', $token);
	$token = preg_replace('/rq/u', 'qq', $token);
	$token = preg_replace('/uv([iea\x{e000}\x{e003}])/u', 'u$1', $token);
	$token = preg_replace('/tt([ie])/u', 'ts$1', $token);

	$token = preg_replace('/\x{e000}$/u', 'ai', $token);
	$token = preg_replace('/\x{e001}/u', 'ts', $token);
	$token = preg_replace('/\x{e003}/u', 'ii', $token);

	return $token;
}

function do_kal_kleinschmidt($otoken) {
	$m = ['', '', '', ''];
	if (preg_match('~^([^\pL\pN\pM]*)([\pL\pN\pM]+.*?)([^\pL\pN\pM]*)$~u', $otoken, $m)) {
		$otoken = $m[2];
	}
	else {
		$m = ['', '', '', ''];
	}

	$token = mb_strtolower($otoken);
	if (preg_match('/\w+/u', $token)) {
		$rv = klein_kal_from($otoken);
		$before = '';
		$after = $token;
		if ($rv != 0) {
			$before = substr($otoken, 0, $rv);
			$after = substr($token, $rv);
		}
		$after = kal_klein2new($after);
		$token = $before . $after;
		if (is_upper(mb_substr($otoken, 0, 1))) {
			$token = mb_ucfirst($token);
		}
	}

	return $m[1].$token.$m[3];
}
