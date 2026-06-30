'use strict';
// Unit test for tools/export-ipa-index.js (the IPA index exporter).
// Self-checking: prints SUCCESS and exits 0, or prints FAILURE and exits 1.
// Run: node test/test-export-ipa.js

const assert = require('assert');
const { clean, buildIndex } = require('../tools/export-ipa-index.js');

function run() {
	// --- clean(): engine display markup -> plain Unicode IPA ---
	assert.strictEqual(clean('a<sup>w</sup>b'), 'aʷb', 'sup w -> ʷ');
	assert.strictEqual(clean('i<sup>j</sup>o'), 'iʲo', 'sup j -> ʲ');
	assert.strictEqual(clean('t<sup>s</sup>i'), 'tˢi', 'sup s -> ˢ');
	assert.strictEqual(clean('x<b>y</b>z'), 'xyz', 'stray tags stripped');
	assert.strictEqual(clean('a   b'), 'a b', 'whitespace collapsed');

	// --- buildIndex(): transcription, filtering, dedup, shape ---
	const doc = buildIndex([
		'qimmeq', 'illu', 'nuna', 'atuarpoq', 'oqaatsit', 'aasiaat',
		'qimmeq',          // duplicate -> collapsed
		"a'a",             // apostrophe -> skipped (engine bails)
		'2',               // digit -> skipped
		'',                // blank -> skipped
	]);

	// Known, parity-pinned transcriptions (engine is deterministic).
	assert.strictEqual(doc.ipa.qimmeq, '¹qim mɜq', 'qimmeq transcription');
	assert.strictEqual(doc.ipa.illu, '¹iɬ ɬu', 'illu transcription');

	// Stress/syllabification-only results are kept (not dropped as no-ops).
	assert.strictEqual(doc.ipa.nuna, 'nu na', 'syllabified-only kept');

	// Markup is fully converted: no raw tags survive, modifiers present.
	for (const [w, ipa] of Object.entries(doc.ipa)) {
		assert.ok(!/[<>]/.test(ipa), `no markup left in "${w}": ${ipa}`);
	}
	assert.ok(doc.ipa.atuarpoq.includes('ʷ'), 'labialization ʷ present');
	assert.ok(doc.ipa.oqaatsit.includes('ˢ'), 'assibilation ˢ present');
	assert.ok(doc.ipa.aasiaat.includes('ʲ'), 'palatalization ʲ present');

	// Filtering: non-alphabetic inputs excluded.
	assert.ok(!("a'a" in doc.ipa), 'apostrophe form skipped');
	assert.ok(!('2' in doc.ipa), 'digit skipped');

	// Dedup: the repeated headword yields a single key.
	const qimmeqKeys = Object.keys(doc.ipa).filter((k) => k === 'qimmeq');
	assert.strictEqual(qimmeqKeys.length, 1, 'duplicate collapsed');

	// Meta count matches the actual entry count.
	assert.strictEqual(doc.meta.count, Object.keys(doc.ipa).length, 'meta.count accurate');
	assert.strictEqual(doc.meta.license, 'CC-BY-SA-4.0', 'data license');
}

try {
	run();
	console.log('SUCCESS');
	process.exit(0);
} catch (err) {
	console.log('FAILURE:\n' + (err && err.stack ? err.stack : err));
	process.exit(1);
}
