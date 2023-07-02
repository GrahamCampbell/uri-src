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

namespace League\Uri;

use League\Uri\Contracts\UriInterface;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
use Stringable;
use function array_pop;
use function array_reduce;
use function count;
use function end;
use function explode;
use function implode;
use function in_array;
use function str_repeat;
use function strpos;
use function substr;

final class BaseUri implements Stringable
{
    private const WHATWG_SPECIAL_SCHEMES = ['ftp', 'http', 'https', 'ws', 'wss'];
    private const REGEXP_ENCODED_CHARS = ',%(2[D|E]|3\d|4[1-9|A-F]|5[\d|AF]|6[1-9|A-F]|7[\d|E]),i';

    /**
     * @var array<string,int>
     */
    private const DOT_SEGMENTS = ['.' => 1, '..' => 1];

    public readonly Psr7UriInterface|UriInterface|null $origin;
    private readonly ?string $nullValue;

    private function __construct(
        public readonly Psr7UriInterface|UriInterface $value
    ) {
        $this->nullValue = $this->value instanceof Psr7UriInterface ? '' : null;
        $this->origin = $this->computeOrigin($this->value, $this->nullValue);
    }

    private function computeOrigin(Psr7UriInterface|UriInterface $uri, ?string $nullValue): Psr7UriInterface|UriInterface|null
    {
        $scheme = $uri->getScheme();
        if ('blob' === $scheme) {
            $uri = Uri::new($uri->getPath());
            $scheme = $uri->getScheme();
        }

        if (!in_array($scheme, self::WHATWG_SPECIAL_SCHEMES, true)) {
            return null;
        }

        return $uri
            ->withFragment($nullValue)
            ->withQuery($nullValue)
            ->withPath('')
            ->withUserInfo($nullValue);
    }

    public static function new(Stringable|string $baseUri): self
    {
        return new self(self::filterUri($baseUri));
    }

    public function __toString(): string
    {
        return $this->value->__toString();
    }

    public function isAbsolute(): bool
    {
        return $this->nullValue !== $this->value->getScheme();
    }

    public function isNetworkPath(): bool
    {
        return $this->nullValue === $this->value->getScheme()
            && $this->nullValue !== $this->value->getAuthority();
    }

    public function isAbsolutePath(): bool
    {
        return $this->nullValue === $this->value->getScheme()
            && $this->nullValue === $this->value->getAuthority()
            && '/' === ($this->value->getPath()[0] ?? '');
    }

    public function isRelativePath(): bool
    {
        return $this->nullValue === $this->value->getScheme()
            && $this->nullValue === $this->value->getAuthority()
            && '/' !== ($this->value->getPath()[0] ?? '');
    }

    /**
     * Tells whether both URI refers to the same document.
     */
    public function isSameDocument(Stringable|string $uri): bool
    {
        return self::normalize(self::filterUri($uri)) === self::normalize($this->value);
    }

    /**
     * Normalizes a URI for comparison.
     */
    private static function normalize(Psr7UriInterface|UriInterface $uri): string
    {
        $null = $uri instanceof Psr7UriInterface ? '' : null;

        $path = $uri->getPath();
        if ('/' === ($path[0] ?? '') || '' !== $uri->getScheme().$uri->getAuthority()) {
            $path = BaseUri::new($uri->withPath('')->withQuery($null))->resolve($uri)->value->getPath();
        }

        $query = $uri->getQuery();
        $pairs = null === $query ? [] : explode('&', $query);
        sort($pairs);

        $value = preg_replace_callback(
            self::REGEXP_ENCODED_CHARS,
            static fn (array $matches): string => rawurldecode($matches[0]),
            [$path, implode('&', $pairs)]
        );

        if (null !== $value) {
            [$path, $query] = $value + ['', $null];
        }

        if ($null !== $uri->getAuthority() && '' === $path) {
            $path = '/';
        }

        return $uri
            ->withHost(Uri::fromComponents(['host' => $uri->getHost()])->getHost())
            ->withPath($path)
            ->withQuery([] === $pairs ? $null : $query)
            ->withFragment($null)
            ->__toString();
    }

    /**
     * Tells whether two URI do not share the same origin.
     *
     * @see UriInfo::getOrigin()
     */
    public function isCrossOrigin(Stringable|string $uri): bool
    {
        return null === $this->origin
            || null === ($uriOrigin = $this->computeOrigin(Uri::new($uri), null))
            || $uriOrigin->__toString() !== $this->origin->__toString();
    }

    /**
     * Input URI normalization to allow Stringable and string URI.
     */
    private static function filterUri(Stringable|string $uri): Psr7UriInterface|UriInterface
    {
        return match (true) {
            $uri instanceof self => $uri->value,
            $uri instanceof Psr7UriInterface, $uri instanceof UriInterface => $uri,
            default => Uri::new($uri),
        };
    }

    /**
     * Resolves a URI against a base URI using RFC3986 rules.
     *
     * This method MUST retain the state of the submitted URI instance, and return
     * a URI instance of the same type that contains the applied modifications.
     *
     * This method MUST be transparent when dealing with error and exceptions.
     * It MUST not alter or silence them apart from validating its own parameters.
     */
    public function resolve(Stringable|string $uri): self
    {
        $uri = self::filterUri($uri);
        $null = $uri instanceof Psr7UriInterface ? '' : null;

        if ($null !== $uri->getScheme()) {
            return new self($uri
                ->withPath(self::removeDotSegments($uri->getPath())));
        }

        if ($null !== $uri->getAuthority()) {
            $scheme = $this->value->getScheme();
            if (null === $scheme || '' === $null) {
                $scheme = '';
            }

            return new self($uri
                ->withScheme($scheme)
                ->withPath(self::removeDotSegments($uri->getPath())));
        }

        $user = $null;
        $pass = null;
        $userInfo = $this->value->getUserInfo();
        if (null !== $userInfo) {
            [$user, $pass] = explode(':', $userInfo, 2) + [1 => null];
        }

        [$path, $query] = $this->resolvePathAndQuery($uri);

        return new self($uri
            ->withPath($this->removeDotSegments($path))
            ->withQuery($query)
            ->withHost($this->value->getHost())
            ->withPort($this->value->getPort())
            ->withUserInfo((string) $user, $pass)
            ->withScheme($this->value->getScheme()))
        ;
    }

    /**
     * Remove dot segments from the URI path.
     */
    private function removeDotSegments(string $path): string
    {
        if (!str_contains($path, '.')) {
            return $path;
        }

        $oldSegments = explode('/', $path);
        $newPath = implode('/', array_reduce($oldSegments, self::reducer(...), []));
        if (isset(self::DOT_SEGMENTS[end($oldSegments)])) {
            $newPath .= '/';
        }

        // @codeCoverageIgnoreStart
        // added because some PSR-7 implementations do not respect RFC3986
        if (str_starts_with($path, '/') && !str_starts_with($newPath, '/')) {
            return '/'.$newPath;
        }
        // @codeCoverageIgnoreEnd

        return $newPath;
    }

    /**
     * Remove dot segments.
     *
     * @return array<int, string>
     */
    private static function reducer(array $carry, string $segment): array
    {
        if ('..' === $segment) {
            array_pop($carry);

            return $carry;
        }

        if (!isset(self::DOT_SEGMENTS[$segment])) {
            $carry[] = $segment;
        }

        return $carry;
    }

    /**
     * Resolves an URI path and query component.
     *
     * @return array{0:string, 1:string|null}
     */
    private function resolvePathAndQuery(Psr7UriInterface|UriInterface $uri): array
    {
        $targetPath = $uri->getPath();
        $null = $uri instanceof Psr7UriInterface ? '' : null;

        if (str_starts_with($targetPath, '/')) {
            return [$targetPath, $uri->getQuery()];
        }

        if ('' === $targetPath) {
            $targetQuery = $uri->getQuery();
            if ($null === $targetQuery) {
                $targetQuery = $this->value->getQuery();
            }

            $targetPath = $this->value->getPath();
            //@codeCoverageIgnoreStart
            //because some PSR-7 Uri implementations allow this RFC3986 forbidden construction
            if (null !== $this->value->getAuthority() && !str_starts_with($targetPath, '/')) {
                $targetPath = '/'.$targetPath;
            }
            //@codeCoverageIgnoreEnd

            return [$targetPath, $targetQuery];
        }

        $basePath = $this->value->getPath();
        if (null !== $this->value->getAuthority() && '' === $basePath) {
            $targetPath = '/'.$targetPath;
        }

        if ('' !== $basePath) {
            $segments = explode('/', $basePath);
            array_pop($segments);
            if ([] !== $segments) {
                $targetPath = implode('/', $segments).'/'.$targetPath;
            }
        }

        return [$targetPath, $uri->getQuery()];
    }

    /**
     * Relativize a URI according to a base URI.
     *
     * This method MUST retain the state of the submitted URI instance, and return
     * a URI instance of the same type that contains the applied modifications.
     *
     * This method MUST be transparent when dealing with error and exceptions.
     * It MUST not alter of silence them apart from validating its own parameters.
     */
    public function relativize(Stringable|string $uri): self
    {
        $uri = self::formatHost(self::filterUri($uri));
        if (!$this->isRelativizable($uri)) {
            return new self($uri);
        }

        $null = $uri instanceof Psr7UriInterface ? '' : null;
        $uri = $uri->withScheme($null)->withPort(null)->withUserInfo($null)->withHost($null);
        $targetPath = $uri->getPath();
        $basePath = $this->value->getPath();

        return new self(match (true) {
            $targetPath !== $basePath => $uri->withPath(self::relativizePath($targetPath, $basePath)),
            self::componentEquals('query', $uri) => $uri->withPath('')->withQuery($null),
            $null === $uri->getQuery() => $uri->withPath(self::formatPathWithEmptyBaseQuery($targetPath)),
            default => $uri->withPath(''),
        });
    }

    /**
     * Tells whether the component value from both URI object equals.
     */
    private function componentEquals(string $property, Psr7UriInterface|UriInterface $uri): bool
    {
        return self::getComponent($property, $uri) === self::getComponent($property, $this->value);
    }

    /**
     * Returns the component value from the submitted URI object.
     */
    private static function getComponent(string $property, Psr7UriInterface|UriInterface $uri): ?string
    {
        $component = match ($property) {
            'query' => $uri->getQuery(),
            'authority' => $uri->getAuthority(),
            default => $uri->getScheme(), //scheme
        };

        if ($uri instanceof Psr7UriInterface && '' === $component) {
            return null;
        }

        return $component;
    }

    /**
     * Filter the URI object.
     */
    private static function formatHost(Psr7UriInterface|UriInterface $uri): Psr7UriInterface|UriInterface
    {
        if (!$uri instanceof Psr7UriInterface) {
            return $uri;
        }

        $host = $uri->getHost();
        if ('' === $host) {
            return $uri;
        }

        return $uri->withHost((string) Uri::fromComponents(['host' => $host])->getHost());
    }

    /**
     * Tells whether the submitted URI object can be relativized.
     */
    private function isRelativizable(Psr7UriInterface|UriInterface $uri): bool
    {
        return !self::new($uri)->isRelativePath()
            && self::componentEquals('scheme', $uri)
            && self::componentEquals('authority', $uri);
    }

    /**
     * Relatives the URI for an authority-less target URI.
     */
    private static function relativizePath(string $path, string $basePath): string
    {
        $baseSegments = self::getSegments($basePath);
        $targetSegments = self::getSegments($path);
        $targetBasename = array_pop($targetSegments);
        array_pop($baseSegments);
        foreach ($baseSegments as $offset => $segment) {
            if (!isset($targetSegments[$offset]) || $segment !== $targetSegments[$offset]) {
                break;
            }
            unset($baseSegments[$offset], $targetSegments[$offset]);
        }
        $targetSegments[] = $targetBasename;

        return self::formatPath(
            str_repeat('../', count($baseSegments)).implode('/', $targetSegments),
            $basePath
        );
    }

    /**
     * returns the path segments.
     *
     * @return string[]
     */
    private static function getSegments(string $path): array
    {
        if ('' !== $path && '/' === $path[0]) {
            $path = substr($path, 1);
        }

        return explode('/', $path);
    }

    /**
     * Formatting the path to keep a valid URI.
     */
    private static function formatPath(string $path, string $basePath): string
    {
        if ('' === $path) {
            return in_array($basePath, ['', '/'], true) ? $basePath : './';
        }

        if (false === ($colonPosition = strpos($path, ':'))) {
            return $path;
        }

        $slashPosition = strpos($path, '/');
        if (false === $slashPosition || $colonPosition < $slashPosition) {
            return "./$path";
        }

        return $path;
    }

    /**
     * Formatting the path to keep a resolvable URI.
     */
    private static function formatPathWithEmptyBaseQuery(string $path): string
    {
        $targetSegments = self::getSegments($path);
        /** @var string $basename */
        $basename = end($targetSegments);

        return '' === $basename ? './' : $basename;
    }
}