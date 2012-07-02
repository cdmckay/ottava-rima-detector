<?php

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/ottava-rima-fitness.php';

$positive_stanza = read_stanza_file(__DIR__ . '/stanza-positives.txt');
echo 'Read ' . count($positive_stanza) . ' positive stanza(s).' . "\n";

$negative_stanza = read_stanza_file(__DIR__ . '/stanza-negatives.txt');
echo 'Read ' . count($negative_stanza) . ' negative stanza(s).' . "\n";

foreach ($positive_stanza as $i => $stanza) {
    $fitness = ottava_rima_fitness($stanza);
    echo "Positive stanza $i " . ($fitness === 200 ? "is" : "is NOT") . " ottava rima.\n";
}
foreach ($negative_stanza as $i => $stanza) {
    $fitness = ottava_rima_fitness($stanza);
    echo "Negative stanza $i " . ($fitness === 200 ? "is" : "is NOT") . " ottava rima.\n";
}

