#!/usr/bin/env node
'use strict';
//
// export-ipa-index.js — RAW-DATA CONVERSION ONLY.
//
// Runs this repo's IPA engine (js/ipa.js, GPL-3) over a list of Kalaallisut
// headwords and emits a normalized pronunciation index:
//
//   { "meta": {...}, "ipa": { "<lowercased-headword>": "<ipa-string>", ... } }
//
// The JSON OUTPUT is data (CC-BY-SA-4.0, matching the Oqaasileriffik source
// lexicon); only this conversion tool runs the GPL engine. Downstream consumers
// (e.g. the `oq` PWA) fetch the JSON as a surface-form pronunciation enrichment
// and never link the engine code.
//
// Usage:
//   node tools/export-ipa-index.js < wordlist.txt > ipa-index.json
//   node tools/export-ipa-index.js wordlist.txt   > ipa-index.json
//
// wordlist.txt: one Kalaallisut headword per line, UTF-8. The canonical source
// is the Oqaasileriffik dictionary headwords (kalaallisut|stem|lexeme); see
// tools/README.md for the extraction one-liner.

const fs = require('fs');
const { kal_ipa_words } = require('../js/ipa.js');

// The engine emits display markup; convert it to plain Unicode IPA.
const SUP = { w: 'ʷ', j: 'ʲ', s: 'ˢ' };

/** @param {string} ipa */
function clean(ipa) {
	return ipa
		.replace(/<sup>([wjs])<\/sup>/g, (_, c) => SUP[c] || '')
		.replace(/<\/?[^>]+>/g, '') // strip any stray tags
		.replace(/\s+/g, ' ')
		.trim();
}

function readInput() {
	const arg = process.argv[2];
	const raw = arg ? fs.readFileSync(arg, 'utf8') : fs.readFileSync(0, 'utf8');
	return raw.split(/\r?\n/);
}

/**
 * Build the normalized IPA index document from a list of raw input lines.
 * Pure (no I/O) so it can be unit-tested directly.
 * @param {string[]} lines
 */
function buildIndex(lines) {
	/** @type {Record<string, string>} */
	const ipa = {};
	const seen = new Set();
	for (const line of lines) {
		const word = line.trim();
		if (!word) continue;
		const lc = word.toLowerCase();
		if (seen.has(lc)) continue;
		seen.add(lc);
		// The engine only transcribes purely-alphabetic Kalaallisut tokens
		// (kal_ipa bails on anything else); skip phrases, apostrophes, digits.
		if (!/^[a-zæøåŋ]+$/i.test(lc)) continue;
		const out = clean(kal_ipa_words(lc));
		// Keep every transcription, including the rare single-syllable word whose
		// IPA equals its spelling — that is still valid pronunciation info. Only
		// empty output (which the alphabetic pre-filter should already preclude)
		// is dropped.
		if (!out) continue;
		ipa[lc] = out;
	}

	// No generated_at timestamp: keep the output deterministic so regenerating
	// produces a clean (empty) git diff when the inputs are unchanged.
	return {
		meta: {
			schema_version: '1.0',
			generated_by: 'Oqaasileriffik-ipa-ks tools/export-ipa-index.js',
			engine: 'js/ipa.js (kal_ipa)',
			license: 'CC-BY-SA-4.0',
			license_note:
				'Generated IPA data. The engine (js/ipa.js) is GPL-3.0; this JSON output is data, licensed CC-BY-SA-4.0 to match the Oqaasileriffik source lexicon.',
			attribution: 'Oqaasileriffik (Greenlandic Language Secretariat)',
			source_repo: 'https://github.com/jandahl/Oqaasileriffik-ipa-ks',
			count: Object.keys(ipa).length,
		},
		ipa,
	};
}

function main() {
	const doc = buildIndex(readInput());
	process.stdout.write(JSON.stringify(doc, null, '\t') + '\n');
}

if (require.main === module) {
	main();
}

module.exports = { clean, buildIndex };
