<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Embed;

use DigitalAnomaly\AlteredLogic\Support\Framework\DependencyInjection;

/**
 * Class for generating embeddings, in a deferred manner.
 */
final class EmbedDefer extends AbstractEmbed
{
    /** @var boolean Whether the embeddings are being generated in a deferred manner or not. */
    protected bool $isDeferred = true;





    /**
     * Create a new, unconfigured EmbedDefer instance.
     *
     * When using a framework, its instantiated using the framework's dependency injection functionality.
     *
     * @return self
     */
    public static function new()
    {
        // Note: the return type is not specified in PHP.
        // This is so the framework can return a mock, intended to act like an EmbedDefer instance

        /** @var self $instance */
        $instance = DependencyInjection::instantiate(self::class);

        return $instance;
    }





    /**
     * Retrieve an embedding. If configured, cache/s will be checked first.
     *
     * @param mixed $source The item to embed - if not a string, it will be encoded as JSON.
     * @return self
     */
    public function fetch(mixed $source): self
    {
        $this->_fetch($source);

        return $this;
    }

    /**
     * Retrieve embeddings. If configured, cache will be checked first.
     *
     * A single request is sent to the AI provider if they support it.
     *
     * @param array<string|integer,mixed> $sources The items to embed - non-string items will be encoded as JSON.
     * @return self
     */
    public function fetchMany(array $sources): self
    {
        $this->_fetchMany($sources);

        return $this;
    }

    /**
     * Flush embeddings - This processes all outstanding embeddings globally (across all models).
     *
     * @return self
     */
    public function flush(): self
    {
        $this->_flush();

        return $this;
    }
}
