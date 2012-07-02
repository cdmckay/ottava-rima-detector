<?php

require_once __DIR__ . '/common.php';
require_once __DIR__ . '/classes/CMUDict.class.php';
require_once __DIR__ . '/classes/Poemosome.class.php';
require_once __DIR__ . '/ottava-rima-fitness.php';

define('MAX_GENERATIONS', 50);
define('OFFSPRING_PER_GENERATION', 50);
define('OTTAVA_RIMA_BREEDER_DEBUG', true);

$generations = array();

// Start with a population of human-written ottava rima.
$generations[0] = array();
$stanzas = read_stanza_file(__DIR__ . '/stanza-positives.txt');
foreach ($stanzas as $stanza) {
    $generations[0][] = Poemosome::fromStanza($stanza);
}

for ($i = 1; $i < MAX_GENERATIONS; $i++) {
    if (OTTAVA_RIMA_BREEDER_DEBUG) {
        echo "Generating generation $i...\n";
    }
    $previous_generation =& $generations[$i - 1];
    $generations[$i] = array();
    $next_generation =& $generations[$i];

    for ($j = 0; $j < (OFFSPRING_PER_GENERATION / 2); $j++) {
        $parent1 = roulette_select_from($previous_generation);
        $parent2 = roulette_select_from($previous_generation);

        if ($parent1 == null || $parent2 == null) {
            echo "Wtf?";
        }

        $offspring1 = Poemosome::fromGenes($parent1->getGenes());
        $offspring2 = Poemosome::fromGenes($parent2->getGenes());
        $offspring1->crossover($offspring2);
        $offspring1->mutate('random_word_mutator');
        $offspring2->mutate('random_word_mutator');

        $next_generation[] = $offspring1;
        $next_generation[] = $offspring2;
    }
}

// Sort the last generation by fitness.
$last_generation = $generations[MAX_GENERATIONS - 1];
$last_generation_with_fitness = array();

foreach ($last_generation as $i => $member) {
    $fitness = ottava_rima_fitness((string) $member);
    $last_generation_with_fitness[$i] = array(
        'member' => $member,
        'fitness' => $fitness
    );
}

uasort($last_generation_with_fitness, function($a, $b) {
    return $a['fitness'] - $b['fitness'];
});

$i = 0;
foreach ($last_generation_with_fitness as $pair) {
    $i++;
    $member = $pair['member'];
    $fitness = $pair['fitness'];
    file_put_contents("poem-$i-fitness-$fitness.txt", (string) $member);
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

