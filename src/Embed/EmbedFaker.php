<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed;

use DigitalAnomaly\AlteredLogic\Embed\Vector;
use DigitalAnomaly\AlteredLogic\Exceptions\EmbedFakerException;

/**
 * A class to assist with faking embeddings.
 *
 * @todo - consider making this a static-entry-pattern class, removing the need to use `new EmbedFaker()`
 */
final class EmbedFaker
{
    /** @var array<string,Vector> Particular embedding vectors to return, specified by the caller. */
    private array $vectors = [];

    /** @var boolean Return real embeddings for embeddings that weren't specified. */
    private bool $dontThrowMode = false;

    /** @var boolean Return random vectors for embeddings that weren't specified. */
    private bool $randomMode = false;

    /** @var boolean Make sure the embedding vectors are returned in the order defined by the caller. */
    private bool $inOrderMode = false;

    /** @var list<string> The order the embeddings should be requested in. */
    private array $order = [];





    /**
     * Specify an embedding that should be returned when requested.
     *
     * @param string $source The source to store the embedding against (e.g. the text that would be embedded).
     * @param Vector $vector The embedding vector to return.
     * @return $this
     */
    public function embedding(string $source, Vector $vector): self
    {
        $this->embeddings([$source => $vector]);

        return $this;
    }

    /**
     * Add embeddings to the list of embeddings to return.
     *
     * @param array<string,Vector> $vectors The embeddings to return.
     * @return $this
     */
    public function embeddings(array $vectors): self
    {
        foreach ($vectors as $source => $embedding) {
            $this->vectors[(string) $source] ??= $embedding;
        }

        return $this;
    }



    /**
     * Don't throw an exception if there are no fake embeddings, real embeddings will be generated and returned instead.
     *
     * @return $this
     */
    public function dontThrow(): self
    {
        $this->dontThrowMode = true;

        return $this;
    }



    /**
     * Generate random embeddings when needed.
     *
     * @return $this
     */
    public function random(): self
    {
        $this->randomMode = true;

        return $this;
    }



    /**
     * Specify order the embeddings should be requested in.
     *
     * @param string[] $order The order to expect embeddings to be requested in.
     * @return $this
     */
    public function inOrder(array $order): self
    {
        $this->inOrderMode = true;

        $this->order = \array_merge($this->order, \array_values($order));

        return $this;
    }





    /**
     * Get a vector, depending on the settings and embeddings stored.
     *
     * @param string  $source     The source the embedding is stored against.
     * @param integer $dimensions The number of dimensions the vector should have.
     * @return Vector|null
     */
    public function getVector(string $source, int $dimensions): ?Vector
    {
        $dimensions = \max($dimensions, 1);

        return $this->inOrderMode
            ? $this->getNextVector($source, $dimensions)
            : $this->getParticularVector($source, $dimensions);
    }

    /**
     * Get the next vector in the order they were added.
     *
     * @param string  $source     The key the embedding is stored against.
     * @param integer $dimensions The number of dimensions the vector should have (when generating a random one).
     * @return Vector|null
     * @throws EmbedFakerException If the vector is not found, is unexpected, or the dimensions don't match.
     */
    private function getNextVector(string $source, int $dimensions): ?Vector
    {
        // check what the next expected key is
        $nextSource = \reset($this->order);
        if (!\is_string($nextSource)) {
            throw EmbedFakerException::nextEmbeddingNotSpecified($source);
        }

        // make sure the caller requested the next expected key
        if ($source !== $nextSource) {
            throw EmbedFakerException::unexpectedFakeEmbeddingRequested($nextSource, $source);
        }

        $vector = $this->getParticularVector($source, $dimensions);

        // remove the key so the next vector can be requested
        \array_shift(array: $this->order);

        return $vector;
    }

    /**
     * Get a vector, depending on the settings and vectors stored.
     *
     * @param string  $source     The key the embedding is stored against.
     * @param integer $dimensions The number of dimensions the vector should have (when generating a random one).
     * @return Vector|null
     * @throws EmbedFakerException If the vector is not found, or the dimensions don't match.
     */
    private function getParticularVector(string $source, int $dimensions): ?Vector
    {
        $vector = null;
        if (\array_key_exists($source, $this->vectors)) {

            $vector = $this->vectors[$source];

            if ($vector->dimensions() !== $dimensions) {
                throw EmbedFakerException::fakeEmbedDimensionsMismatch(
                    $source,
                    $dimensions,
                    $vector->dimensions(),
                );
            }

            return $vector;

        } elseif ($this->randomMode) {

            $vector = $this->generateRandomVector($dimensions);
            $this->vectors[$source] = $vector; // store it so the same vector is returned for the same key next time

            return $vector;

        } elseif ($this->dontThrowMode) {

            return null;
        }

        throw EmbedFakerException::fakeEmbeddingNotFound($source);
    }

    /**
     * Generate a random vector.
     *
     * @param integer $dimensions The number of dimensions the vector should have.
     * @return Vector
     */
    private function generateRandomVector(int $dimensions): Vector
    {
        $embedding = [];
        for ($i = 0; $i < $dimensions; $i++) {

            // generate a random float between -1 and 1
            $embedding[] = (\mt_rand() / \mt_getrandmax()) * 2 - 1;
        }

        return new Vector($embedding);
    }
}
