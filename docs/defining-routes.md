# Defining Routes

Every time you add a route to the _Map_, you get back a _Route_ object. The _Route_ object is pretty powerful, and allows you to define a wide range of matching conditions. All of the _Route_ methods are fluent, so you can chain them together.

## Placeholder Tokens and Default Values

When you add a `{token}` placeholer in the path, it uses a default regular expression of `[^/]`. Essentially, this matches everything except a slash, which of course indicates the next path segment.

To define custom regular expressions for placeholder tokens, use the `tokens()` method.

```php
<?php
$map->get('blog.read', '/blog/{id}')
    ->tokens(['id' => '\d+'])
?>
```

The _Route_ object does not predefine any tokens for you. One that you may find useful is a `{format}` token, to specify an optional dot-format extension at the end of a file name:

```php
<?php
$map->get('blog.read', '/blog/{id}{format}')
    ->tokens([
        'id' => '\d+',
        'format' => '(\.[^/]+)?',
    ]);
?>
```

If no default value is specified for a placeholder token, the corresponding attribute value will be `null`. To set your own default values, call the `defaults()` method.

```php
$map->post('blog.archive', '/blog/{id}{format}')
    ->defaults([
        'format' => '.html',
    ]);
```


### Optional Placeholder Tokens

Sometimes it is useful to have a route with optional placeholder tokens for attributes. None, some, or all of the optional values may be present, and the route will still match.

To specify optional attributes, use the notation `{/attribute1,attribute2,attribute3}` in the path. For example:

```php
<?php
$map->get('archive', '/archive{/year,month,day}')
    ->tokens([
        'year' => '/d{4}',
        'month' => '/d{2}',
        'day' => '/d{2}',
    ]);
?>
```

Note that the leading slash separator is *inside* the placeholder token, not outside.

With that, the following paths will all match the 'archive' route, and set the attribute values accordingly:

- `/archive            : ['year' => null,   'month' => null, 'day' = null]`
- `/archive/1979       : ['year' => '1979', 'month' => null, 'day' = null]`
- `/archive/1979/11    : ['year' => '1979', 'month' => '11', 'day' = null]`
- `/archive/1979/11/07 : ['year' => '1979', 'month' => '11', 'day' = '01']`

Optional attributes are *sequentially* optional. This means that, in the above example, you cannot have a "day" without a "month", and you cannot have a "month" without a "year".

You can have only one set of optional attributes in a route path.

Optional attributes belong at the end of a route path. Placing them elsewhere may result in unexpected behavior.

## Wildcard Attributes

Sometimes it is useful to allow the trailing part of the path be anything at all. To allow arbitrary trailing path segments on a route, call the `wildcard()` method. This will let you specify the attribute name under which the arbitrary trailing values will be stored.

```php
<?php
$map->get('wild', '/wild')
    ->wildcard('card');
?>
```
All slash-separated path segments after the `{id}` will be captured as an array in the in wildcard attribute. For example:

- '/wild'             : ['card' => []]
- '/wild/foo'         : ['card' => ['foo']]
- '/wild/foo/bar'     : ['card' => ['foo', 'bar']]
- '/wild/foo/bar/baz' : ['card' => ['foo', 'bar', 'baz']]


## Host Matching

You can limit a route to specific hosts with the `host()` method and a regular expression. The following example will only match when the request is on `example.com` domain:

```php
$map->get('blog.browse', '/blog')
    ->host('example.com');
```

(Dots in the regular expression will automatically be escaped for you.)

You can use placeholder tokens and default values in the host specification, and capture those values into route attributes. The following matches `*.example.com` and captures the subdomain value as a route attribute:

```php
$map->get('blog.browse', '/blog')
    ->host('{subdomain}.?example.com');
```

## Accept Headers

The `accepts()` method adds to a list of content types that the route handler can be expected to return.

```php
$map->get('blog.browse', '/blog')
    ->accepts([
        'application/json',
        'application/xml',
        'text/csv',
    ]);
```

Note that this is *not* a content negotiation method. It is only a pro-forma check to see if one of the specified types is present in the `Accept` header with a non-zero `q` value. THe handler, or some other layer, should perform content negotation proper.

## Multiple HTTP Verbs

The `allows()` method adds to the allowed HTTP verbs for the route.

```php
$map->post('blog.edit', '/blog/{id}')
    ->allows(['PATCH', 'PUT'])
```

## Secure Protocols

You can use the `secure()` method to specify that a route should only match a secure protcol. (Specifically, `$_SERVER['HTTPS']` must be on, or the request must be on port 443.)

```php
$map->post('blog.edit', '/blog/{id}')
    ->secure();
```

You can call `secure(false)` to limit the route to only non-secure protocols. Calling `secure(null)` causes the route to ignore the protocol security.

## Non-Routable Routes

Sometimes you will want to have a route in the _Map_ that is used only for generating paths, and not for matching to handlers.  In this case, you can call `isRoutable(false)`. (This is rare but useful.)

## Custom Extras

Other, custom data about the route can be stored using the `extras()` method. Pass an array of key-value pairs and it will be merged with any other custom data already stored.

```php
$map->post('blog.dashboard', '/blog/dashboard')
    ->extras([
        'authenticated' => true,
        'is_admin' => true,
    ]);
```

You can then use these extra values in your own custom matching rules.

## Default Map Route Specifications

You can call any of the above _Route_ methods on the _Map_. When you do so, the _Map_ will then use those as the defaults for every route you add thereafter. This is useful for defining a base set of placeholder token expressions, default values, and so on.

```php
<?php
// define defaults for all routes added hereafter
$map->tokens([
    'id' => '\d+',
    'format' => '(\.[^/]+)?',
])->defaults([
    'format' => '.json',
])->host(
    '{subdomain}.?'
)->accepts([
    'application/json',
    'application/xml',
    'text/html',
]);

// each added route now uses the map defaults
$map->get('blog.browse', '/blog');
$map->get('blog.read', '/blog/{id}{format}');
$map->patch('blog.edit', '/blog/{id}');
$map->put('blog.add', '/blog');
$map->delete('blog.delete', '/blog/{id}');
?>
```