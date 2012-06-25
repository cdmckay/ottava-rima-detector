<?php

require_once __DIR__ . '/classes/CMUDict.class.php';
require_once __DIR__ . '/classes/Poemosome.class.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/ottava-rima-fitness.php';

define('MAX_GENERATIONS', 20);
define('OFFSPRING_PER_GENERATION', 22);

$generations = array();

// Start with a population of human-written ottava rima.
$generations[0] = array();
$stanzas = read_stanza_file(__DIR__ . '/stanza-positives.txt');
foreach ($stanzas as $stanza) {
    $generations[0][] = Poemosome::fromStanza($stanza);
}

for ($i = 1; $i < MAX_GENERATIONS; $i++) {
    $previous_generation = $generations[$i - 1];
    $next_generation = $generations[$i] = array();

    for ($j = 0; $j < OFFSPRING_PER_GENERATION; $j++) {
        $parent1 = roulette_select_from($previous_generation);
        $parent2 = roulette_select_from($previous_generation);

        $offspring1 = Poemosome::fromGenes($parent1->getGenes());
        $offspring2 = Poemosome::fromGenes($parent2->getGenes());
        $offspring1->crossover($offspring2);
        $offspring1->mutate('random_word_mutator');
        $offspring2->mutate('random_word_mutator');

        $next_generation[] = $offspring1;
        $next_generation[] = $offspring2;
    }
}

/**
 * @param $members
 * @return Poemosome
 */
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

/**
 * @param $gene
 * @return string
 */
function random_word_mutator($gene) {
    return CMUDict::get()->getRandomWord();
}

