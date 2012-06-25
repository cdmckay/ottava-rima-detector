<?php

class Poemosome {

    private $genes;
    private $delimiter;
    private $crossoverRate = 0.7;
    private $mutationRate = 0.001;

    private function __construct($genes, $delimiter) {
        $this->genes = $genes;
        $this->delimiter = $delimiter;
    }

    static function fromStanza(
        $stanza,
        $delimiter = "\n"
    ) {
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

        return new Poemosome($genes, $delimiter);
    }

    static function fromGenes(
        $genes,
        $delimiter = "\n"
    ) {
        return new Poemosome($genes, $delimiter);
    }

    function crossover(Poemosome $poemosome) {
        if (mt_rand(0, 1000 - 1) > $this->crossoverRate * 1000) {
            return;
        }

        $genes1 = $this->genes;
        $genes2 = $poemosome->getGenes();

        // The chromosomes might have a different number of genes, so we need to
        // determine a maximum point we can crossover at.
        $max = min(count($genes1), count($genes2));
        $point = mt_rand(0, $max);

        $newGenes1 = array_merge(array_slice($genes1, 0, $point), array_slice($genes2, $point));
        $newGenes2 = array_merge(array_slice($genes2, 0, $point), array_slice($genes1, $point));

        $this->setGenes($newGenes1);
        $poemosome->setGenes($newGenes2);
    }

    function mutate($mutator) {
        foreach ($this->genes as $i => $gene) {
            if (mt_rand(0, 1000 - 1) <= $this->mutationRate * 1000) {
                $this->genes[$i] = $mutator($this->genes[$i]);
            }
        }
    }

    function __toString() {
        $stanza = implode(' ', $this->genes);
        $stanza = str_replace(" \n ", "\n", $stanza);
        return $stanza;
    }

    public function getGenes() {
        return $this->genes;
    }

    public function setGenes($genes) {
        $this->genes = $genes;
    }

}