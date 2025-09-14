<?php

declare(strict_types=1);

namespace DigitalAnomaly\AlteredLogic\Adapters\AiProviders\OpenAI\Modex\Transformers;

use BackedEnum;
use DigitalAnomaly\AlteredLogic\Interfaces\Modex\Schemas\ToolInterface;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ToolTypes\FunctionTool;
use DigitalAnomaly\AlteredLogic\Modex\Internal\ToolTypes\WebSearchTool;
use DigitalAnomaly\AlteredLogic\Support\ArrayHelper;
use DigitalAnomaly\Schema\Enums\NativeTypeEnum;
use DigitalAnomaly\Schema\Schema;
use DigitalAnomaly\Schema\Types\ArrayType;
use DigitalAnomaly\Schema\Types\CallableType;
use DigitalAnomaly\Schema\Types\ClassType;
use DigitalAnomaly\Schema\Types\EnumType;
use DigitalAnomaly\Schema\Types\InterfaceType;
use DigitalAnomaly\Schema\Types\IntersectionType;
use DigitalAnomaly\Schema\Types\NativeType;
use DigitalAnomaly\Schema\Types\TraitType;
use DigitalAnomaly\Schema\Types\UnionType;

/**
 * Transforms tools and structured responses into a form ready to send to the OpenAI Responses API.
 *
 * The output generated has strict mode enabled.
 *
 * @see https://platform.openai.com/docs/guides/function-calling
 * @see https://platform.openai.com/docs/guides/structured-outputs
 * @see https://platform.openai.com/docs/guides/structured-outputs#supported-schemas
 * @see https://platform.openai.com/docs/guides/structured-outputs#refusals
 * @see https://platform.openai.com/docs/guides/structured-outputs#objects-have-limitations-on-nesting-depth-and-size
 * - "A schema may have up to 100 object properties total, with up to 5 levels of nesting."
 */
final class OpenAiResponsesApiOutboundStructuredDataTransformer
{
    /**
     * Transform a tool into a form ready for a request body, for the OpenAI Responses API.
     *
     * @param ToolInterface $tool The tool to transform.
     * @return array<string,mixed>
     */
    public static function transformTool(ToolInterface $tool): array
    {
        // FunctionTool
        if ($tool instanceof FunctionTool) {

            $hasParameters = \count($tool->callableType->parameters) > 0;
            $parameterSchema = new Schema(
                ClassType::newAnonymous($tool->callableType->parameters),
            );

            $return = [
                'type' => 'function',
                'name' => $tool->callableType->name,
                'description' => $tool->callableType->description,
                'parameters' => $parameters = self::buildSchema($parameterSchema),
                'strict' => true,
            ];

            // handle the special case where there are no parameters - the responses API requires it to be set like this
            if (!$hasParameters) {
                unset($return['parameters'], $return['strict']);
            }

            return ArrayHelper::removeEmptyElements($return, ['description', 'parameters']);

        // WebSearchTool
        } elseif ($tool instanceof WebSearchTool) {

            $userLocation = [
                'type' => 'approximate',
                'city' => $tool->userLocationCity,
                'country' => $tool->userLocationCountry,
                'region' => $tool->userLocationRegion,
                'timezone' => $tool->userLocationTimezone,
            ];
            $userLocation = ArrayHelper::removeEmptyElements($userLocation, ['city', 'country', 'region', 'timezone']);
            $userLocation = \count($userLocation) > 1
                ? $userLocation
                : null;

            $return = [
                'type' => $tool->name,
                'search_context_size' => $tool->searchContextSize,
                'user_location' => $userLocation,
            ];
            return ArrayHelper::removeEmptyElements($return, ['search_context_size', 'user_location']);
        }

        throw new \Exception("OpenAI Resources API - unsupported tool: " . \get_class($tool)); // todo - throw a custom exception
    }



    /**
     * Transform a structured response into a form ready for a request body, for the OpenAI Responses API.
     *
     * @param Schema $schema The structured response to transform.
     * @return array<string,mixed>
     */
    public static function transformStructuredResponse(Schema $schema): array
    {
        return self::buildSchema($schema);
    }







    /**
     * Transform schemas for the OpenAI Responses API.
     *
     * @param array<string,Schema> $schemas The schemas to transform.
     * @param integer              $depth   The current nesting-depth of the schema.
     * @return array<string,mixed>
     */
    private static function buildSchemas(array $schemas, int $depth = 1): array
    {
        $return = [];
        foreach ($schemas as $schema) {
            $return[$schema->name] = self::buildSchema($schema, $depth);
        }

        return $return;
    }

    /**
     * Builds a schema for the AI provider.
     *
     * @param Schema  $schema The schema to transform.
     * @param integer $depth  The current nesting-depth of the schema.
     * @return array<string,mixed>
     */
    private static function buildSchema(Schema $schema, int $depth = 1): array
    {
        // dd($schema);
        // todo - throw an error if depth >= 5

        // when the type was not specified
        if ($schema->type === null) {

            // default to a scalar type
            $return = [
                'type' => ['boolean', 'integer', 'number', 'string', 'null'],
                'description' => $schema->description,
            ];

            return ArrayHelper::removeEmptyElements($return, ['description']);

            // throw new \Exception("OpenAI Resources API - The type for \"{$schema->name}\" was not specified"); // todo - throw a custom exception
        }

        // NativeType
        if ($schema->type instanceof NativeType) {

            $return = [
                'type' => self::resolveNativeType($schema->type, $schema->nullable),
                'description' => $schema->description,
            ];
            return ArrayHelper::removeEmptyElements($return, ['description']);
        }

        // ArrayType
        if ($schema->type instanceof ArrayType) {

            if ($schema->type->type instanceof UnionType) {
                throw new \Exception("OpenAI Resources API - arrays can only have one type"); // todo - throw a custom exception
            }

            if ($schema->type->keyType !== NativeTypeEnum::Integer) {
                throw new \Exception("OpenAI Resources API - arrays can only have integer keys"); // todo - throw a custom exception
            }

            $description = $schema->description;
            $schema = new Schema($schema->type->type);

            $return = [
                'type' => 'array',
                'description' => $description,
                'items' => self::buildSchema($schema, $depth), // todo - is $depth correct, or $depth + 1?
            ];
            return ArrayHelper::removeEmptyElements($return, ['description']);
        }

        // CallableType
        if ($schema->type instanceof CallableType) {

            $return = [
                'type' => 'object',
                'description' => $schema->description,
                'properties' => self::buildSchemas($schema->type->parameters, $depth + 1),
                'required' => \array_keys($schema->type->parameters),
                'additionalProperties' => false,
            ];
            return ArrayHelper::removeEmptyElements($return, ['description']);
        }

        // ClassType
        if ($schema->type instanceof ClassType) {

            $return = [
                'type' => 'object',
                'description' => $schema->description,
                'properties' => self::buildSchemas($schema->type->children, $depth + 1),
                'required' => \array_keys($schema->type->children),
                'additionalProperties' => false,
            ];
            return ArrayHelper::removeEmptyElements($return, ['description']);
        }

        // EnumType
        if ($schema->type instanceof EnumType) {

            $values = $schema->type->fqcn !== null
                ? self::getEnumValues($schema->type->fqcn)
                : $schema->type->values;

            $return = [
                'type' => 'string',
                'description' => $schema->description,
                'enum' => $values,
            ];

            return ArrayHelper::removeEmptyElements($return, ['description']);
        }

        // InterfaceType
        if ($schema->type instanceof InterfaceType) {
        }

        // IntersectionType
        if ($schema->type instanceof IntersectionType) {
        }

        // TraitType
        if ($schema->type instanceof TraitType) {
        }

        // UnionType
        if ($schema->type instanceof UnionType) {

            $anyOfSchemas = [];
            foreach ($schema->type->types as $type) {
                $childSchema = new Schema($type, '', $schema->description);
                $anyOfSchemas[] = self::buildSchema($childSchema, $depth); // todo - is $depth correct, or $depth + 1?
            }
            return [
                'anyOf' => $anyOfSchemas,
            ];
        }

        // todo
        $class = \get_class($schema->type);
        throw new \Exception("OpenAI Resources API - unsupported type: {$class}");
    }

    /**
     * Converts the PHP type to a type recognised by OpenAI Responses API.
     *
     * @param NativeType|null $type       The type to resolve.
     * @param boolean         $isOptional Whether the type is optional or not.
     * @return string|array<string>
     */
    private static function resolveNativeType(?NativeType $type, bool $isOptional = false): string|array
    {
        // when the type hasn't been specified
        if ($type === null) {
            return 'null';
        }



        $typeMap = [
            NativeTypeEnum::Boolean->value => 'boolean',
            // NativeTypeEnum::False->value => 'boolean',
            // NativeTypeEnum::True->value => 'boolean',
            NativeTypeEnum::Integer->value => 'integer',
            NativeTypeEnum::Float->value => 'number',
            NativeTypeEnum::String->value => 'string',
            // NativeTypeEnum::Array->value => 'array', // todo - implement differently
            NativeTypeEnum::Object->value => 'object',
            // NativeTypeEnum::Resource->value => ;
            // NativeTypeEnum::Callable->value => ,
            // NativeTypeEnum::Iterable->value => 'array', // todo - implement differently
            // NativeTypeEnum::Mixed->value => ,
            NativeTypeEnum::Null->value => 'null',
            // NativeTypeEnum::Void->value => 'null', // todo - treat void as null?
            // NativeTypeEnum::Never->value => 'null', // todo - treat never as null?
        ];

        $return = [];
        $return[] = \array_key_exists($type->type->value, $typeMap)
            ? $typeMap[$type->type->value]
            : throw new \Exception("OpenAI Resources API - unsupported type: {$type->type->value}"); // todo - throw a custom exception



        if ($isOptional) {
            $return[] = 'null';
        }


        // if there's only one type, return it as a string
        $return = \array_unique($return);
        return \count($return) === 1
            ? $return[0]
            : $return;
    }



    /**
     * Get the values of an enum, as an array of strings.
     *
     * @param string $enumFqcn The enum to get the values of.
     * @return list<int|string>
     */
    private static function getEnumValues(string $enumFqcn): array
    {
        if (!\enum_exists($enumFqcn)) {
            throw new \Exception("OpenAI Resources API - {$enumFqcn} is not a valid PHP enum"); // todo - throw custom exception
        }

        return \array_map(
            fn($case) => $case instanceof BackedEnum
                ? $case->value
                : $case->name,
            $enumFqcn::cases()
        );
    }
}

/*
{
    "model": "gpt-4o",
    "input": "What is the weather like in Boston today?",
    "tools": [
      {
        "type": "function",
        "name": "get_current_weather",
        "description": "Get the current weather in a given location",
        "parameters": {
          "type": "object",
          "properties": {
            "location": {
              "type": "string",
              "description": "The city and state, e.g. San Francisco, CA"
            },
            "unit": {
              "type": "string",
              "enum": ["celsius", "fahrenheit"]
            }
          },
          "required": ["location", "unit"]
        }
      }
    ],
    "tool_choice": "auto"
  }
*/
