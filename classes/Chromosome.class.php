<?php

class Chromosome {

    private $delimiter;
    private $syllable_tolerance;

    private $genes;

    function __construct(
        $stanza,
        $delimiter = "\n",
        $syllable_tolerance = 2
    ) {
        $this->delimiter = $delimiter;
        $this->syllable_tolerance = $syllable_tolerance;

        // All the genes in the chromosome.
        $genes = array();

        // Separate the stanza into lines.
        $lines = explode($delimiter, trim($stanza));

        foreach ($lines as $line) {
            // Adapted from:
            // http://stackoverflow.com/questions/790596/split-a-text-into-single-words
            // This will split on a group of one or more whitespace characters, but also suck in any surrounding
            // punctuation characters. It also matches punctuation characters at the beginning or end of the string.
            // This discriminates cases such as "don't" and "he said 'ouch!'"
            $line_words = preg_split('/((^\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+$))/', $line, -1, PREG_SPLIT_NO_EMPTY);

            $genes = array_merge($genes, $line_words);
            $genes[] = $delimiter;
        }

        $this->genes = $genes;
    }

    function crossover(Chromosome $chromosome) {
        $genes1 = $this->getGenes();
        $genes2 = $chromosome->getGenes();

        // The chromosomes might have a different number of genes, so we need to
        // determine a maximum point we can crossover at.
        $max = min(count($genes1), count($genes2));
        $point = mt_rand(0, $max);

        $newGenes1 = array_merge(array_slice($genes1, 0, $point), array_slice($genes2, $point));
        $newGenes2 = array_merge(array_slice($genes2, 0, $point), array_slice($genes1, $point));

        $this->setGenes($newGenes1);
        $chromosome->setGenes($newGenes2);
    }

    function __toString() {
        $stanza = implode(' ', $this->genes);
        $stanza = str_replace(" \n ", "\n", $stanza);
        return $stanza;
    }

    public function setGenes($genes) {
        $this->genes = $genes;
    }

    public function getGenes() {
        return $this->genes;
    }

}