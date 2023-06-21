---
layout: default
title: URI resolver - relativizer
---

URI resolver - relativizer
=======

The `League\Uri\UriResolver` class enable resolving or relativizing a URI using a base URI. 

<p class="message-notice">All the methods accepts string or Stringable objects like the PSR-7 or League own <code>UriInterface</code> implementing class.</p>

## Resolving a relative URI

The `UriResolver::resolve` public static method provides the mean for resolving a URI as a
browser would for a relative URI. When performing URI resolution the returned URI is
normalized according to RFC3986 rules. The uri to resolved must be another Uri object.

~~~php
<?php

use League\Uri\Http;
use League\Uri\UriResolver;

$newUri = UriResolver::resolve("./p#~toto", "http://www.example.com/path/to/the/sky/"); //returns an League\Uri\Uri object

echo $newUri; //displays "http://www.example.com/path/to/the/sky/p#~toto"
~~~

## Relativize an URI

The `UriResolver::relativize` public static method provides the mean to construct a relative
URI that when resolved against the same URI yields the same given URI. This modifier does
the inverse of the Resolve modifier. The uri to relativize must be another Uri object.

~~~php
$baseUri = Uri::new('http://www.example.com');
$uri = Uri::new('http://www.example.com/?foo=toto#~typo');

$relativeUri = UriResolver::relativize($uri, $baseUri);
echo $relativeUri; // display "/?foo=toto#~typo
echo UriResolver::resolve($relativeUri, $baseUri);
// display 'http://www.example.com/?foo=toto#~typo'
~~~