---
layout: default
title: The Data Uri Path component
---

# Data URI Path

The library provides a `DataPath` class to ease complex path manipulation on a Data URI object. This URI component object exposes :

- the [package common API](/components/7.0/api/)
- the [path common API](/components/7.0/path)

but also provide specific methods to work with Data URI paths.

## Instantiation

~~~php
<?php
public static DataPath::new(Stringable|string $value = ''): self
public static DataPath::fromUri(Stringable|string $uri): self
~~~

<p class="message-notice">submitted string is normalized to be <code>RFC3986</code> compliant.</p>

<p class="message-warning">If the submitted value is not valid a <code>League\Uri\Contracts\UriException</code> exception is thrown.</p>

~~~php
<?php

use League\Uri\Components\DataPath;

DataPath::new()->value(); //returns 'text/plain;charset=us-ascii,'
~~~

## Instantiation using a file path

<p class="message-warning">The named constructor <code>createFromPath</code> is deprecated starting with version <code>2.3.0</code>. You should use the <code>createFromFilePath</code> named constructor instead.</p>

~~~php
<?php
public static function DataPath::fromFilePath(Stringable|string $path): self
public static function DataPath::fromPath(Stringable|string $path): self
~~~

Because data URI represents files you can also instantiate a new data URI object from a file path using the `createFromPath` named constructor.

~~~php
<?php

use League\Uri\Components\DataPath;

$path = DataPath::fromFilePath('path/to/my/png/image.png');
echo $uri; //returns 'image/png;charset=binary;base64,...'
//where '...' represent the base64 representation of the file
~~~

If the file is not readable or accessible a `League\Uri\Components\Exception` exception will be thrown. The class uses PHP's `finfo` class to detect the required mediatype as defined in `RFC2045`.

## Accessing the path properties

The DataPath class exposes the following specific methods:

- `getMediaType`: Returns the Data URI current mediatype;
- `getMimeType`: Returns the Data URI current mimetype;
- `getParameters`: Returns the parameters associated with the mediatype;
- `getData`: Returns the encoded data contained is the Data URI;
- `isBinaryData`: Tells whether the data URI represents some binary data

Each of these methods return a string. This string can be empty if the data where no supplied when constructing the URI.

~~~php
<?php

use League\Uri\Components\DataPath ;

$path = DataPath::new('text/plain;charset=us-ascii,Hello%20World%21');
echo $path->getMediaType(); //returns 'text/plain;charset=us-ascii'
echo $path->getMimeType(); //returns 'text/plain'
echo $path->getParameters(); //returns 'charset=us-ascii'
echo $path->getData(); //returns 'Hello%20World%21'
$path->isBinaryData(); //returns false

$binary_path = DataPath::fromFilePath('path/to/my/png/image.png');
$binary_path->isBinaryData(); //returns true
~~~

## Modifying the path properties

### Update the Data URI parameters

Since we are dealing with a data and not just a URI, the only property that can be modified are its optional parameters.

To set new parameters you should use the `withParameters` method:

~~~php
<?php

use League\Uri\Components\DataPath;

$path = DataPath::new('text/plain;charset=us-ascii,Hello%20World%21');
$newPath = $path->withParameters('charset=utf-8');
echo $newPath; //returns 'text/plain;charset=utf-8,Hello%20World%21'
~~~

<p class="message-notice">Of note the data should be urlencoded if needed.</p>

### Transcode the data between its binary and ascii representation

Another manipulation is to transcode the data from ASCII to is base64 encoded (or binary) version. If no conversion is possible the former object is returned otherwise a new valid data uri object is created.

~~~php
<?php

use League\Uri\Components\DataPath;

$path = DataPath::new('text/plain;charset=us-ascii,Hello%20World%21');
$path->isBinaryData(); // return false;
$newPath = $path->toBinary();
$newPath->isBinaryData(); //return true;
$newPath->toAscii() == $path; //return true;
~~~

## Saving the data path

Since the path can be interpreted as a file, it is possible to save it to a specified path using the dedicated `save` method. This method accepts two parameters:

- the file path;
- the open mode (à la PHP `fopen`);

By default the open mode is set to `w`. If for any reason the file is not accessible a `RuntimeException` will be thrown.

The method returns the `SplFileObject` object used to save the data-uri data for further analysis/manipulation if you want.

~~~php
<?php

use League\Uri\Components\DataPath;

$path = DataPath::fromFilePath('path/to/my/file.png');
$file = $uri->save('path/where/to/save/my/image.png');
//$file is a SplFileObject which point to the newly created file;
~~~
