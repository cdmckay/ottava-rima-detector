<?php

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/fitness.php';
require_once __DIR__ . '/classes/Chromosome.class.php';

define('MAX_GENERATIONS', 20);

$generations = array();

// Start with a population of human-written ottava rima.
$generations[0] = array();
$stanzas = read_stanza_file(__DIR__ . '/stanza-positives.txt');
foreach ($stanzas as $stanza) {
    $generations[0][] = new Chromosome($stanza);
}

for ($i = 0; $i < MAX_GENERATIONS; $i++) {

    $members = $generations[$i];

    $member1 = roulette_select_from($members);
    $member2 = roulette_select_from($members);

}

function roulette_select_from($members) {
    $fitnesses = array();

    $sum = 0;
    foreach ($members as $i => $member) {
        $fitness = ottava_rima_fitness((string) $member);
        $sum += $fitness;
        $fitnesses[$i] = $fitness;
    }

    $slice = mt_rand(0, $sum);
    $slice_sum = 0;
    foreach ($members as $i => $member) {
        $slice_sum += $fitnesses[$i];
        if ($slice_sum >= $slice) {
            return $member;
        }
    }

    return last($fitnesses);
}

