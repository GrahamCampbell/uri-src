<?php

/**
 * League.Uri (https://uri.thephpleague.com)
 *
 * (c) Ignace Nyamagana Butera <nyamsprod@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace League\Uri\Components;

use League\Uri\Contracts\UriComponentInterface;
use League\Uri\Contracts\UriInterface;
use League\Uri\Exceptions\SyntaxError;
use League\Uri\Http;
use League\Uri\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface as Psr7UriInterface;
use Stringable;

/**
 * @group scheme
 * @coversDefaultClass \League\Uri\Components\Scheme
 */
final class SchemeTest extends TestCase
{
    /**
     * @covers ::validate
     */
    public function testWithContent(): void
    {
        self::assertEquals(Scheme::createFromString('ftp'), Scheme::createFromString('FtP'));
    }

    /**
     * @dataProvider validSchemeProvider
     *
     * @covers ::validate
     * @covers ::filterComponent
     * @covers ::__toString
     * @covers ::getUriComponent
     */
    public function testValidScheme(
        UriComponentInterface|Stringable|string|null $scheme,
        string $toString,
        string $uriComponent
    ): void {
        $scheme = null !== $scheme ? Scheme::createFromString($scheme) : Scheme::createFromNull();

        self::assertSame($toString, (string) $scheme);
        self::assertSame($uriComponent, $scheme->getUriComponent());
    }

    public static function validSchemeProvider(): array
    {
        return [
            [null, '', ''],
            [Scheme::createFromString('foo'), 'foo', 'foo:'],
            [new class() {
                public function __toString()
                {
                    return 'foo';
                }
            }, 'foo', 'foo:'],
            ['a', 'a', 'a:'],
            ['ftp', 'ftp', 'ftp:'],
            ['HtTps', 'https', 'https:'],
            ['wSs', 'wss', 'wss:'],
            ['telnEt', 'telnet', 'telnet:'],
        ];
    }

    /**
     * @dataProvider invalidSchemeProvider
     *
     * @covers ::validate
     * @covers ::filterComponent
     */
    public function testInvalidScheme(string $scheme): void
    {
        $this->expectException(SyntaxError::class);

        Scheme::createFromString($scheme);
    }

    public static function invalidSchemeProvider(): array
    {
        return [
            'empty string' => [''],
            'invalid char' => ['in,valid'],
            'integer like string' => ['123'],
        ];
    }

    /**
     * @dataProvider getURIProvider
     * @covers ::createFromUri
     */
    public function testCreateFromUri(UriInterface|Psr7UriInterface $uri, ?string $expected): void
    {
        self::assertSame($expected, Scheme::createFromUri($uri)->value());
    }

    public static function getURIProvider(): iterable
    {
        return [
            'PSR-7 URI object' => [
                'uri' => Http::createFromString('http://example.com?foo=bar'),
                'expected' => 'http',
            ],
            'PSR-7 URI object with no scheme' => [
                'uri' => Http::createFromString('//example.com/path'),
                'expected' => null,
            ],
            'League URI object' => [
                'uri' => Uri::createFromString('http://example.com?foo=bar'),
                'expected' => 'http',
            ],
            'League URI object with no scheme' => [
                'uri' => Uri::createFromString('//example.com/path'),
                'expected' => null,
            ],
        ];
    }
}