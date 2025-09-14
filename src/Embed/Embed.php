<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed;

use DigitalAnomaly\AlteredLogic\Support\EmbedHelper;
use DigitalAnomaly\AlteredLogic\Support\Framework\DependencyInjection;

/**
 * Class for generating embeddings.
 */
final class Embed extends AbstractEmbed
{
    /** @var boolean Whether the embeddings are being generated in a deferred manner or not. */
    protected bool $isDeferred = false;





    /**
     * Create a new, unconfigured Embed instance.
     *
     * When using a framework, its instantiated using the framework's dependency injection functionality.
     *
     * @return self
     */
    public static function new()
    {
        // Note: the return type is not specified in PHP.
        // This is so the framework can return a mock, intended to act like an Embed instance

        /** @var self $instance */
        $instance = DependencyInjection::instantiate(self::class);

        return $instance;
    }





    /**
     * Retrieve an embedding. If configured, cache/s will be checked first.
     *
     * @param mixed $source The item to embed - if not a string, it will be encoded as JSON.
     * @return Vector|null The embedding.
     */
    public function fetch(mixed $source): ?Vector
    {
        return $this->_fetch($source);
    }

    /**
     * Retrieve embeddings. If configured, cache will be checked first.
     *
     * A single request is sent to the AI provider if they support it.
     *
     * @param array<string|integer,mixed> $sources The items to embed - non-string items will be encoded as JSON.
     * @return array<integer,Vector|null> The embeddings, keyed by their position in the $sources array.
     */
    public function fetchMany(array $sources): array
    {
        return $this->_fetchMany($sources);
    }

    /**
     * Generate PHP code defining a faker object with the given embeddings.
     *
     * @param array<string|integer,mixed> $sources     The items to embed - non-string items will be encoded as JSON.
     * @param boolean                     $arrayFormat Whether to render the Vectors into an array first, or add them
     *                                                 individually to the faker.
     * @return string The PHP code.
     */
    public function toPhp(array $sources, bool $arrayFormat = false): string
    {
        $sources = EmbedHelper::normaliseSources($sources);

        $vectors = $this->_fetchMany($sources);



        $values = [];
        foreach ($sources as $index => $source) {

            $values[$source] = $vectors[$index] !== null
                ? $vectors[$index]->toPhp()
                : 'null';
        }



        $output = [];
        if ($arrayFormat) {

            $output[] = '$vectors = [';
            foreach ($values as $source => $value) {
                $output[] = "    '{$source}' => {$value},";
            }
            $output[] = '];';

            $output[] = '$faker = new EmbedFaker()->embeddings($vectors);';
        } else {

            $output[] = '$faker = new EmbedFaker()';
            foreach ($values as $source => $value) {
                $isLast = $source === \array_key_last($values);
                $output[] = "    ->embedding('{$source}', {$value})" . ($isLast ? ';' : '');
            }
        }

        $output[] = 'EmbedConfig::faker($faker);';



        return \PHP_EOL . \implode(\PHP_EOL, $output) . \PHP_EOL . \PHP_EOL;
    }
}
