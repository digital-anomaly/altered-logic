<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Exceptions;

/**
 * Exceptions related to Documents.
 */
final class DocumentException extends AlteredLogicException
{
    // /**
    //  * Thrown when a document profile is not found.
    //  *
    //  * @param string $profile The name of the document profile that was not found.
    //  * @return self
    //  */
    // public static function documentProfileNotFound(string $profile): self
    // {
    //     return new self("The document profile \"{$profile}\" was not found");
    // }





    /**
     * Thrown when no doc-searchers have been attached (to the DocumentProfile).
     *
     * @return self
     */
    public static function noDocSearchersHaveBeenAttached(): self
    {
        return new self('No doc-searchers have been attached to the document profile');
    }

    /**
     * Thrown when a doc-searcher hasn't been attached (to the DocumentProfile).
     *
     * @param string   $name      The name of the doc-searcher that hasn't been attached.
     * @param string[] $searchers The names of the searchers that are available.
     * @return self
     */
    public static function docSearcherIsntAttached(string $name, array $searchers): self
    {
        $searchersString = \implode('", "', $searchers);

        return new self(
            \count($searchers) > 0
                ? "The doc-searcher \"{$name}\" hasn't been attached to the document profile. "
                    . "Available: \"{$searchersString}\""
                : "The doc-searcher \"{$name}\" hasn't been attached to the document profile. "
                    . "No doc-searchers have been attached to the document profile",
        );
    }

    /**
     * Thrown when multiple searchers are specified (only one is allowed for searching).
     *
     * @param string[] $searchers The names of the searchers that are available.
     * @return self
     */
    public static function multipleSearchersNotAllowed(array $searchers): self
    {
        $searchers = \implode('", "', $searchers);

        return new self("Please specify only one searcher to use when searching. Available: \"{$searchers}\"");
    }





    /**
     * Thrown when a value is not a Document instance.
     *
     * @param string $expectedType The expected type of the value.
     * @return self
     */
    public static function invalidType(string $expectedType): self
    {
        return new self("All items must be instances of {$expectedType}");
    }





    /**
     * Thrown when a empty string category is given.
     *
     * @return self
     */
    public static function categoryCannotBeAnEmptyString(): self
    {
        return new self('The category cannot be an empty string');
    }

    /**
     * Thrown when an empty string identifier is given.
     *
     * @return self
     */
    public static function identifierCannotBeAnEmptyString(): self
    {
        return new self('The identifier cannot be an empty string');
    }





    /**
     * Thrown when a method call requires forDocument() to be called beforehand.
     *
     * @param string $method The method that was called.
     * @return self
     */
    public static function callForDocFirst(string $method): self
    {
        return new self("Please call forDocument() first before calling {$method}()");
    }

    // /**
    //  * Thrown when a method call requires forCategory() to be called beforehand.
    //  *
    //  * @param string $method The method that was called.
    //  * @return self
    //  */
    // public static function callForCatFirst(string $method): self
    // {
    //     return new self("Please call forCategory() first before calling {$method}()");
    // }

    /**
     * Thrown when a method call requires forDocument() or forCategory() to be called beforehand.
     *
     * @param string $method The method that was called.
     * @return self
     */
    public static function callForDocOrForCatFirst(string $method): self
    {
        return new self("Please call forDocument() or forCategory() first before calling {$method}()");
    }

    /**
     * Thrown when a method call requires forDocument() or forCategory() to NOT be called beforehand.
     *
     * @param string $method The method that was called.
     * @return self
     */
    public static function dontCallForDocOrForCatFirst(string $method): self
    {
        return new self("Please don't call forDocument() or forCategory() before calling {$method}()");
    }

    /**
     * Thrown when a method call requires a category to be passed, but none was.
     *
     * @param string $method The method that was attempted.
     * @return self
     */
    public static function specifyACategory(string $method): self
    {
        return new self(
            "Please specify a category when calling {$method}()"
        );
    }
}
