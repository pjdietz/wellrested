<?php

namespace WellRESTed\Routing\Route;

use RuntimeException;

/**
 * @internal
 */
class RegexRoute extends Route
{
    /** @var array */
    private $captures = [];

    public function getType(): int
    {
        return Route::TYPE_PATTERN;
    }

    /**
     * Examines a request target to see if it is a match for the route.
     *
     * @param string $requestTarget
     * @return bool
     */
    public function matchesRequestTarget(string $requestTarget): bool
    {
        $this->captures = [];
        $matched = preg_match($this->getTarget(), $requestTarget, $captures);
        if ($matched) {
            $this->captures = $captures;
            return true;
        } elseif ($matched === false) {
            throw new RuntimeException('Invalid regular expression: ' . $this->getTarget());
        }
        return false;
    }

    /**
     * Returns an array of matches from the last call to matchesRequestTarget.
     *
     * @see \preg_match
     * @return array
     */
    public function getPathVariables(): array
    {
        return $this->captures;
    }
}
