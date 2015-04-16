<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 *
 */
namespace Aura\Router;

use Psr\Http\Message\ServerRequestInterface;

/**
 *
 * An individual route with a name, path, attributes, defaults, etc.
 *
 * In general, you should never need to instantiate a Route directly. Use the
 * Map instead.
 *
 * @package Aura.Router
 *
 * @property-read string $name The route name.
 *
 * @property-read string $path The route path.
 *
 * @property-read array $defaults Default values for attributes.
 *
 * @property-read array $attributes Attribute values added by the rules.
 *
 * @property-read array $tokens The regular expression for the route.
 *
 * @property-read string $wildcard The name of the wildcard attribute.
 *
 */
class Route
{
    /**
     *
     * The name for this Route.
     *
     * @var string
     *
     */
    protected $name;

    /**
     *
     * The path for this Route with attribute tokens.
     *
     * @var string
     *
     */
    protected $path;

    /**
     *
     * Token names and regexes.
     *
     * @var array
     *
     */
    protected $tokens = array();

    /**
     *
     * Server keys and regexes.
     *
     * @var array
     *
     */
    protected $server = array();

    /**
     *
     * HTTP method(s).
     *
     * @var array
     *
     */
    protected $method = array();

    /**
     *
     * Accept header values.
     *
     * @var array
     *
     */
    protected $accept = array();

    /**
     *
     * Default attribute values.
     *
     * @var array
     *
     */
    protected $defaults = array();

    /**
     *
     * Secure route?
     *
     * @var bool
     *
     */
    protected $secure = null;

    /**
     *
     * Wildcard token name, if any.
     *
     * @var string
     *
     */
    protected $wildcard = null;

    /**
     *
     * Routable route?
     *
     * @var bool
     *
     */
    protected $routable = true;

    /**
     *
     * Attribute values added by the rules.
     *
     * @var array
     *
     */
    protected $attributes = [];

    /**
     *
     * The rule that failed, if any, during matching.
     *
     * @var string
     *
     */
    protected $failedRule;

    /**
     *
     * A prefix to add to the name.
     *
     * @var string
     *
     */
    protected $namePrefix;

    /**
     *
     * A prefix to add to the path.
     *
     * @var string
     *
     */
    protected $pathPrefix;

    /**
     *
     * Magic read-only for all properties.
     *
     * @param string $key The property to read from.
     *
     * @return mixed
     *
     */
    public function __get($key)
    {
        return $this->$key;
    }

    public function __clone()
    {
        // $this is the cloned instance, not the original
        $this->attributes = $this->defaults;
        $this->failedRule = null;
    }

    public function appendPathPrefix($pathPrefix)
    {
        if ($this->path !== null) {
            $message = __CLASS__ . '::$pathPrefix is immutable once $path is set';
            throw new Exception\ImmutableProperty($message);
        }
        $this->pathPrefix .= $pathPrefix;
        return $this;
    }

    public function appendNamePrefix($namePrefix)
    {
        if ($this->name !== null) {
            $message = __CLASS__ . '::$namePrefix is immutable once $name is set';
            throw new Exception\ImmutableProperty($message);
        }
        $this->namePrefix .= $namePrefix;
        return $this;
    }

    public function setPath($path)
    {
        if ($this->path !== null) {
            $message = __CLASS__ . '::$path is immutable once set';
            throw new Exception\ImmutableProperty($message);
        }
        $this->path = $this->pathPrefix . $path;
        return $this;
    }

    public function setName($name)
    {
        if ($this->name !== null) {
            $message = __CLASS__ . '::$name is immutable once set';
            throw new Exception\ImmutableProperty($message);
        }
        $this->name = $this->namePrefix . $name;
        return $this;
    }

    /**
     *
     * Sets the regular expressions for attribute tokens.
     *
     * @param array $tokens The regular expressions for attribute tokens.
     *
     * @return $this
     *
     */
    public function setTokens(array $tokens)
    {
        $this->tokens = array();
        return $this->addTokens($tokens);
    }

    /**
     *
     * Merges with the existing regular expressions for attribute tokens.
     *
     * @param array $tokens Regular expressions for attribute tokens.
     *
     * @return $this
     *
     */
    public function addTokens(array $tokens)
    {
        $this->tokens = array_merge($this->tokens, $tokens);
        return $this;
    }

    /**
     *
     * Sets the regular expressions for server values.
     *
     * @param array $server The regular expressions for server values.
     *
     * @return $this
     *
     */
    public function setServer(array $server)
    {
        $this->server = array();
        return $this->addServer($server);
    }

    /**
     *
     * Merges with the existing regular expressions for server values.
     *
     * @param array $server Regular expressions for server values.
     *
     * @return $this
     *
     */
    public function addServer(array $server)
    {
        $this->server = array_merge($this->server, $server);
        return $this;
    }

    /**
     *
     * Sets the allowable method(s), overwriting previous the previous value.
     *
     * @param string|array $method The allowable method(s).
     *
     * @return $this
     *
     */
    public function setMethods($method)
    {
        $this->method = array();
        return $this->addMethods($method);
    }

    /**
     *
     * Adds to the allowable method(s).
     *
     * @param string|array $method The allowable method(s).
     *
     * @return $this
     *
     */
    public function addMethods($method)
    {
        $this->method = array_merge($this->method, (array) $method);
        return $this;
    }

    /**
     *
     * Sets the list of matchable content-types, overwriting previous values.
     *
     * @param string|array $accept The matchable content-types.
     *
     * @return $this
     *
     */
    public function setAccept($accept)
    {
        $this->accept = array();
        return $this->addAccept($accept);
    }

    /**
     *
     * Adds to the list of matchable content-types.
     *
     * @param string|array $accept The matchable content-types.
     *
     * @return $this
     *
     */
    public function addAccept($accept)
    {
        $this->accept = array_merge($this->accept, (array) $accept);
        return $this;
    }

    /**
     *
     * Sets the default values for attributes.
     *
     * @param array $values Default values for attributes.
     *
     * @return $this
     *
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = array();
        return $this->addDefaults($defaults);
    }

    /**
     *
     * Merges with the existing default values for attributes.
     *
     * @param array $defaults Default values for attributes.
     *
     * @return $this
     *
     */
    public function addDefaults(array $defaults)
    {
        $this->defaults = array_merge($this->defaults, $defaults);
        return $this;
    }

    /**
     *
     * Sets whether or not the route must be secure.
     *
     * @param bool $secure If true, the server must indicate an HTTPS request;
     * if false, it must *not* be HTTPS; if null, it doesn't matter.
     *
     * @return $this
     *
     */
    public function setSecure($secure = true)
    {
        $this->secure = ($secure === null) ? null : (bool) $secure;
        return $this;
    }

    /**
     *
     * Sets the name of the wildcard attribute.
     *
     * @param string $wildcard The name of the wildcard attribute, if any.
     *
     * @return $this
     *
     */
    public function setWildcard($wildcard)
    {
        $this->wildcard = $wildcard;
        return $this;
    }

    /**
     *
     * Sets whether or not this route should be used for matching.
     *
     * @param bool $routable If true, this route can be matched; if not, it
     * can be used only to generate a path.
     *
     * @return $this
     *
     */
    public function setRoutable($routable = true)
    {
        $this->routable = (bool) $routable;
        return $this;
    }

    /**
     *
     * Adds attributes to the Route.
     *
     * @param array $attributes The attributes to add.
     *
     * @return null
     *
     */
    public function addAttributes(array $attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);
        return $this;
    }

    public function setFailedRule($failedRule)
    {
        $this->failedRule = $failedRule;
        return $this;
    }
}
