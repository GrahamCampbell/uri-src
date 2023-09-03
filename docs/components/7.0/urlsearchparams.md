---
layout: default
title: URL Search Params
---

URLSearchParams
=======

## Contracts

The `URLSearchParams` class implements utility methods to work with the query string of a URL
as defined by the [WHATWG group](https://url.spec.whatwg.org/#urlsearchparams).

This means that you can use this class anytime you need a compliant `URLSearchParams` object.
To improve its usage the class also exposes the [package common API](/components/7.0/) and implements
the following PHP interfaces:

- `Countable`,
- `IteratorAggregate`,
- `Stringablew,`
- and `JsonSerializable` 

To get the full information on the class behaviour and feature you can go to the 
[MDN page](https://developer.mozilla.org/en-US/docs/Web/API/URLSearchParams). But since we are in a PHP context the class differs
in the following aspects:

| WHATWG group Specification       | PHP implementation              |
|----------------------------------|---------------------------------|
| `URLSearchParams::size` property | `URLSearchParams::sixe()`method |
| `URLSearchParams::forEach()`     | `URLSearchParams::each()`       |

<p class="message-notice">As per the specification the class is mutable.</p>
<p class="message-notice">As per the specification encoding is done follwoing the <code>application/x-www-form-urlencoded</code></p> 

## Usage

### Instantiation

To instantiate a new instance you can use the default constructor which follow the specification
or more specialized named constructors to avoid subtle bugs. 

- The `URLSearchParams::new` named constructor allow instantiating the object from a string or a stringable object.
- The `URLSearchParams::fromUri` named constructor allow instantiating the object from a string or a stringable object.
- The `URLSearchParams::fromRecords` named constructor allow instantiating the object from an object with public properties or generic iterator.
- The `URLSearchParams::fromPairs` named constructor allow instantiating the object from an iterator which produces pairs.
- The `URLSearchParams::fromParameters` named constructor allow instantiating the object from PHP query parameters.

```php
$parameters = [
    'filter' => [
        'dateRange' => [
            'start' => '2023-01-01',
            'end' => '2023-08-31',
        ],
    ],
];

$params = URLSearchParams::fromParameters($parameters);
$params->get('filter[dateRange][start]'); //returns '2023-01-01
echo $params; 
//display "filter%5BdateRange%5D%5Bstart%5D=2023-01-01&filter%5BdateRange%5D%5Bend%5D=2023-08-31"

$interval = new DateInterval('P3MT12M5S');
echo URLSearchParams::fromRecords($interval)->toString();
//display "y=0&m=3&d=0&h=0&i=12&s=5&f=0&invert=0&days=false&from_string=false"
`````

<p class="message-warning"><code>URLSearchParams::new</code> To adhere to the specification, if a string starts with the
character <code>?</code> the character will be skip before parsing the string.</p>

```php
use League\Uri\Components\URLSearchParams;

echo URLSearchParams::new('?a=b')->toString();
echo URLSearchParams::new('a=b')->toString(); 
echo (new URLSearchParams('?a=b'))->toString();
//all the above code will display 'a=b'
//to preserve the question makr you need to encode it.
[...URLSearchParams::new('%3Fa=b')]; // returns [['?a', 'b']]
[...(new URLSearchParams('%3Fa=b'))];  // returns [['?a', 'b']]
`````

### Accessing and manipulating the data

While the class implements all the metods define in the RFC, the follwoing methods are added to ease usage.

- `URLSearchParams::isEmpty`
- `URLSearchParams::isNotEmpty`

~~~php
use League\Uri\Components\URLSearchParams;

$params = new URLSearchParams('foo=bar&bar=baz+bar&foo=baz');
$params->isNotEmpty(); //return true
$params->get('foo'); //returns 'bar'
$params->getAll('foo'); //returns ['bar', 'baz']
$params->has('foo'); //returns true
$params->has('foo', 'bar'); //returns true
$params->has('foo', 'toto'); //returns false (the second parameters is the value of the pair)
count($params); //returns 3
$params->size();  //returns 3
$params->delete('foo');
count($params); //returns 1 (because all the pairs which contains foo have been removed)
$params->set('aha', true);
$params->append('aha', null);
$params->get('foo'); //returns null
$params->get('bar'); //returns "baz bar"
$params->sort();
echo $params->toString(); //returns "aha=true&aha=null&bar=baz+bar"
~~~