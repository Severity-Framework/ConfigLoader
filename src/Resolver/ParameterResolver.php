<?php declare(strict_types=1);

namespace Severity\ConfigLoader\Resolver;

use Severity\ConfigLoader\Contracts\ResolverInterface;
use function preg_match;
use function preg_match_all;
use function str_replace;
use function strlen;
use function strpos;
use function substr;
use function substr_replace;
use const PREG_OFFSET_CAPTURE;

class ParameterResolver implements ResolverInterface
{
    protected array $matches = [];

    public function isMatching(string $parameterValue): bool
    {
        if (preg_match_all('/(?<!\\\)%(?:[a-zA-Z0-9\-_\[\]]|(\\\%))+(?<!\\\)%/', $parameterValue, $matches, PREG_OFFSET_CAPTURE) > 0) {
            $this->matches = $matches;

            return true;
        }

        return false;
    }

    public function translate(string $parameterValue, ResolveContext $context)
    {
        $diff = 0;
        foreach ($this->matches[0] as [$match, $pos]) {
            $length = strlen($match);
            $match = substr(str_replace('\%', '%', $match), 1, -1);

            if (preg_match('/^(?P<var>[a-zA-Z0-9\-_%]+)(?:\[(\g<var>)\])+$/', $match) === 1) {
                $firstComponentPos = strpos($match, '[');
                $firstPart = substr($match, 0, $firstComponentPos);

                $rest = str_replace('][', '.', substr($match, $firstComponentPos + 1, -1));

                $match = "{$firstPart}.{$rest}";
            }

            $resolved = $context->get($match);

            $parameterValue = substr_replace($parameterValue, $resolved, $pos - $diff, $length);

            $diff += $length - strlen($resolved);
        }

        return str_replace('\%', '%', $parameterValue);
    }
}
