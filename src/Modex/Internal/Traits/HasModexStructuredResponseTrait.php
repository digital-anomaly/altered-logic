<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Modex\Internal\Traits;

use DigitalAnomaly\AlteredLogic\Exceptions\ModexException;
use DigitalAnomaly\AlteredLogic\Modex\Internal\SchemaModifier;
use DigitalAnomaly\AlteredLogic\Support\Class\CallableInspector;
use DigitalAnomaly\Schema\BuildSchema;
use DigitalAnomaly\Schema\Schema;

/**
 * Trait that provides methods to configure the structured response for a Modex request.
 */
trait HasModexStructuredResponseTrait
{
    /** @var Schema|null The structured response to use. */
    public ?Schema $structuredResponse = null;



    /**
     * Set the structured response.
     *
     * @param Schema|null $schema The schema to set.
     * @return void
     */
    public function setStructuredResponse(?Schema $schema): void
    {
        // todo - clone $schema here if needed?

        self::postProcessSchema($schema);

        $this->structuredResponse = $schema;
    }

    /**
     * Adjust the Schema with logic this package needs.
     *
     * @param Schema|null $schema The schema to update.
     * @return void
     */
    private static function postProcessSchema(?Schema $schema): void
    {
        if ($schema === null) {
            return;
        }

        SchemaModifier::addNativePHPClassComments($schema);
    }







    /**
     * Specify that the response should be a boolean.
     *
     * @param string $description The description to use.
     * @return void
     */
    public function booleanResponse(string $description = ''): void
    {
        $this->setStructuredResponse(
            BuildSchema::boolean($description)
        );
    }

    /**
     * Specify that the response should be an integer.
     *
     * @param string $description The description to use.
     * @return void
     */
    public function integerResponse(string $description = ''): void
    {
        $this->setStructuredResponse(
            BuildSchema::integer($description)
        );
    }

    /**
     * Specify that the response should be a float.
     *
     * @param string $description The description to use.
     * @return void
     */
    public function floatResponse(string $description = ''): void
    {
        $this->setStructuredResponse(
            BuildSchema::float($description)
        );
    }

    /**
     * Specify that the response should be a string.
     *
     * @return void
     */
    public function stringResponse(): void
    {
        $this->setStructuredResponse(null); // i.e. remove the structured response, reverting to a plain-text response
    }

    /**
     * Specify that the response should be an enum.
     *
     * @param string[] $values      The allowed values.
     * @param string   $description The description to use.
     * @return void
     */
    public function enumResponse(array $values, string $description = ''): void
    {
        $this->setStructuredResponse(
            BuildSchema::enum($values, $description)
        );
    }

    /**
     * Add a structured response to the request.
     *
     * @param callable|object|array{0:class-string|object,1:string}|class-string|string $type The structure to use.
     * @return void
     */
    public function structuredResponse(callable|object|array|string $type): void
    {
        $schema = self::buildSchema($type, false);
        if ($schema === null) {
            throw new \Exception('Could not use structured response type: ' . \gettype($type)); // todo - add a custom exception
        }

        $this->setStructuredResponse($schema);
    }





    /**
     * Specify that the response should be an array of booleans.
     *
     * @param string $description The description to use.
     * @return void
     */
    public function booleanArrayResponse(string $description = ''): void
    {
        $this->setStructuredResponse(
            BuildSchema::booleanArray($description)
        );
    }

    /**
     * Specify that the response should be an array of integers.
     *
     * @param string $description The description to use.
     * @return void
     */
    public function integerArrayResponse(string $description = ''): void
    {
        $this->setStructuredResponse(
            BuildSchema::integerArray($description)
        );
    }

    /**
     * Specify that the response should be an array of floats.
     *
     * @param string $description The description to use.
     * @return void
     */
    public function floatArrayResponse(string $description = ''): void
    {
        $this->setStructuredResponse(
            BuildSchema::floatArray($description)
        );
    }

    /**
     * Specify that the response should be an array of strings.
     *
     * @param string $description The description to use.
     * @return void
     */
    public function stringArrayResponse(string $description = ''): void
    {
        $this->setStructuredResponse(
            BuildSchema::stringArray($description)
        );
    }

    /**
     * Specify that the response should be an array of enums.
     *
     * @param string[] $values      The allowed values.
     * @param string   $description The description to use.
     * @return void
     */
    public function enumArrayResponse(array $values, string $description = ''): void
    {
        $this->setStructuredResponse(
            BuildSchema::enumArray($values, $description)
        );
    }

    /**
     * Add an array of a structured response to the request.
     *
     * @param callable|object|array{0:class-string|object,1:string}|class-string|string $type The structure to use.
     * @return void
     */
    public function structuredResponseArrayOf(callable|object|array|string $type): void
    {
        $schema = self::buildSchema($type, true);
        if ($schema === null) {
            throw new \Exception('Could not use structured response type: ' . \gettype($type)); // todo - add a custom exception
        }

        $this->setStructuredResponse($schema);
    }





    /**
     * Build a Schema instance from a type, if it's callable.
     *
     * @param callable|object|array{0:class-string|object,1:string}|class-string|string $type        The type to build
     *                                                                                               from.
     * @param boolean                                                                   $wrapInArray Whether to wrap the
     *                                                                                               schema in an array.
     * @return Schema|null
     */
    private static function buildSchema(callable|object|array|string $type, bool $wrapInArray): ?Schema
    {
        // // special case - check to see if the $type is a string
        // // which refers to a method from the calling object
        // if (\is_string($type) && \mb_strpos($type, '::') === false && !\class_exists($type)) {

        //     $framesBack = 3;
        //     $backtrace = \debug_backtrace(
        //         \DEBUG_BACKTRACE_PROVIDE_OBJECT | \DEBUG_BACKTRACE_IGNORE_ARGS,
        //         $framesBack + 1,
        //     );
        //     $callingObject = $backtrace[$framesBack]['object'] ?? null;

        //     if (($callingObject !== null) && (\method_exists($callingObject, $type))) {
        //         $type = [$callingObject, $type];
        //     }
        // }



        $i = CallableInspector::newStructuredResponse($type);

        // build the $schema - from a class
        if ($i->refersToClassConstructor()) {

            $schema = $wrapInArray
                ? BuildSchema::arrayFromClass($i->classFqcn)
                : BuildSchema::fromClass($i->classFqcn);

        // build the $schema - from a callable
        } else {

            $callableType = $i->buildCallableType($i->className);
            $schema = $wrapInArray
                ? BuildSchema::arrayFromCallableType($callableType)
                : BuildSchema::fromCallableType($callableType);
        }



        if ($schema === null) {
            return null;
        }

        self::ensureSchemaHasParameters($schema, $wrapInArray);

        return $schema;
    }

    /**
     * Ensure that the Schema has at least one parameter (otherwise it can't be used as a structured response).
     *
     * @param Schema  $schema      The schema to check.
     * @param boolean $wrapInArray Whether the schema is wrapped in an array.
     * @return void
     * @throws ModexException If the schema has no parameters.
     */
    private static function ensureSchemaHasParameters(Schema $schema, bool $wrapInArray): void
    {
        $type = $wrapInArray
            ? ($schema->type?->type ?? null)
            : $schema->type;

        $count = \count($type?->children ?? $type?->parameters ?? []);
        if ($count > 0) {
            return;
        }

        throw ModexException::callablesMustHaveAtLeastOneParameterAsStructuredResponse();
    }
}
