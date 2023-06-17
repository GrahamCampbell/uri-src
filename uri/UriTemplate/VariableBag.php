<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace League\Uri\UriTemplate;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use League\Uri\Exceptions\TemplateCanNotBeExpanded;
use Stringable;
use Traversable;
use function is_bool;
use function is_scalar;

/**
 * @internal The class exposes the internal representation of variable bags
 *
 * @phpstan-type InputValue string|bool|int|float|array<string|bool|int|float>
 * @implements ArrayAccess<string, InputValue>
 * @implements IteratorAggregate<string, InputValue>
 */
final class VariableBag implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * @var array<string,string|array<string>>
     */
    private array $variables = [];

    /**
     * @param iterable<array-key, InputValue> $variables
     */
    public function __construct(iterable $variables = [])
    {
        foreach ($variables as $name => $value) {
            $this->assign((string) $name, $value);
        }
    }

    public static function fromTemplate(Template $template, iterable $inputVariables): self
    {
        if (!$inputVariables instanceof VariableBag) {
            $inputVariables = new VariableBag($inputVariables);
        }

        $variableBag = [];
        foreach ($template->variableNames as $name) {
            if (isset($inputVariables[$name])) {
                $variableBag[$name] = $inputVariables[$name];
            }
        }

        if ($inputVariables->variables === $variableBag) {
            return $inputVariables;
        }

        return new self($variableBag);
    }

    public function count(): int
    {
        return count($this->variables);
    }

    public function getIterator(): Traversable
    {
        yield from $this->variables;
    }

    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->variables);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->variables[$offset]);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->assign($offset, $value); /* @phpstan-ignore-line */
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->fetch($offset);
    }

    /**
     * Tells whether the bag is empty or not.
     */
    public function isEmpty(): bool
    {
        return [] === $this->variables;
    }

    /**
     * Tells whether the bag is empty or not.
     */
    public function isNotEmpty(): bool
    {
        return [] !== $this->variables;
    }

    /**
     * Fetches the variable value if none found returns null.
     *
     * @return null|string|array<string>
     */
    public function fetch(string $name): null|string|array
    {
        return $this->variables[$name] ?? null;
    }

    /**
     * @param Stringable|InputValue $value
     */
    public function assign(string $name, Stringable|string|bool|int|float|array|null $value): void
    {
        $this->variables[$name] = $this->normalizeValue($value, $name, true);
    }

    /**
     * @param Stringable|InputValue $value
     *
     * @throws TemplateCanNotBeExpanded if the value contains nested list
     */
    private function normalizeValue(Stringable|array|string|float|int|bool|null $value, string $name, bool $isNestedListAllowed): array|string
    {
        return match (true) {
            is_bool($value) => true === $value ? '1' : '0',
            (null === $value || is_scalar($value) || $value instanceof Stringable) => (string) $value,
            !$isNestedListAllowed => throw TemplateCanNotBeExpanded::dueToNestedListOfValue($name),
            default => array_map(fn ($var): array|string => self::normalizeValue($var, $name, false), $value),
        };
    }

    /**
     * Replaces elements from passed variables into the current instance.
     */
    public function replace(VariableBag $variables): self
    {
        return new self($this->variables + $variables->variables);
    }
}
