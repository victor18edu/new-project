<?php

namespace App\Support;

use Illuminate\Http\Request;

final class FormLimits
{
    /**
     * Extract the character limits declared in a form request's rules.
     *
     * @param  class-string  $formRequestClass
     * @return array<string, int>
     */
    public static function from(Request $request, string $formRequestClass): array
    {
        $limits = [];

        foreach (self::rules($request, $formRequestClass) as $field => $rules) {
            foreach ($rules as $rule) {
                if (! is_string($rule)) {
                    continue;
                }

                if (preg_match('/(?:^|\|)max:(\d+)(?:\||$)/', $rule, $matches) === 1) {
                    $limits[$field] = (int) $matches[1];

                    continue;
                }

                if (preg_match('/(?:^|\|)size:(\d+)(?:\||$)/', $rule, $matches) === 1) {
                    $limits[$field] = (int) $matches[1];
                }
            }
        }

        return $limits;
    }

    /**
     * Extract the decimal numeric limits declared in a form request's rules.
     *
     * @param  class-string  $formRequestClass
     * @return array<string, string>
     */
    public static function numericMaxFrom(Request $request, string $formRequestClass): array
    {
        $limits = [];

        foreach (self::rules($request, $formRequestClass) as $field => $rules) {
            foreach ($rules as $rule) {
                if (is_string($rule) && preg_match('/(?:^|\|)max:(\d+\.\d+)(?:\||$)/', $rule, $matches) === 1) {
                    $limits[$field] = $matches[1];
                }
            }
        }

        return $limits;
    }

    /**
     * @param  class-string  $formRequestClass
     * @return array<string, array<mixed>>
     */
    private static function rules(Request $request, string $formRequestClass): array
    {
        $formRequest = $formRequestClass::createFrom($request);
        $formRequest->setContainer(app())->setRedirector(app('redirect'));

        $rules = [];

        foreach ($formRequest->rules() as $field => $fieldRules) {
            $rules[$field] = is_array($fieldRules) ? $fieldRules : [$fieldRules];
        }

        return $rules;
    }
}
