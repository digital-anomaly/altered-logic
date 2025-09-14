<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal\ToolTypes;

/**
 * A tool that can be used to search the web.
 */
final readonly class WebSearchTool extends ServerSideTool
{
    /**
     * Constructor.
     *
     * @param string $name                 The web search tool type.
     * @param string $searchContextSize    The search context size.
     * @param string $userLocationCity     The user's location city.
     * @param string $userLocationCountry  The user's ISO 3166-1 alpha-2 country code.
     * @param string $userLocationRegion   The user's location region.
     * @param string $userLocationTimezone The user's IANA timezone.
     */
    public function __construct(
        public string $name = 'web_search_preview',
        public string $searchContextSize = '', // low, medium, high (default = medium)
        public string $userLocationCity = '',
        public string $userLocationCountry = '', // https://en.wikipedia.org/wiki/ISO_3166-1
        public string $userLocationRegion = '',
        public string $userLocationTimezone = '', // https://timeapi.io/documentation/iana-timezones
    ) {}



    /**
     * Get the name of the tool, this identifies it to the AI provider.
     *
     * @return string The name of the tool type.
     */
    public function getName(): string
    {
        return $this->name;
    }
}
