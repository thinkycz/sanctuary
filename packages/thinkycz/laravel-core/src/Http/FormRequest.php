<?php

declare(strict_types=1);

namespace Thinkycz\LaravelCore\Http;

use Illuminate\Foundation\Http\FormRequest as IlluminateFormRequest;
use Illuminate\Validation\Factory as ValidatorFactory;
use Thinkycz\LaravelCore\Support\Parser;
use Thinkycz\LaravelCore\Support\Resolver;
use Thinkycz\LaravelCore\Support\Thrower;
use Thinkycz\LaravelCore\Traits\AssertTrait;
use Thinkycz\LaravelCore\Traits\ParserTrait;
use Thinkycz\LaravelCore\Traits\ParseTrait;

class FormRequest extends IlluminateFormRequest
{
    use AssertTrait;
    use ParserTrait;
    use ParseTrait;

    /**
     * Mixed getter.
     */
    public function mixed(string|null $key = null): mixed
    {
        if ($key === null) {
            return $this->all();
        }

        return $this->input($key);
    }

    /**
     * Validator factory getter.
     */
    public function validatorFactory(): ValidatorFactory
    {
        return Resolver::resolveValidatorFactory();
    }

    /**
     * Thrower getter.
     */
    public function thrower(): Thrower
    {
        return new Thrower($this->validatorFactory()->make([], []));
    }

    /**
     * Signature getter.
     */
    public function signature(): RequestSignature
    {
        return new RequestSignature($this);
    }

    /**
     * Validate request data.
     *
     * @param array<mixed> $rules
     */
    public function validate(array|null $rules = null): Parser
    {
        if ($rules === null) {
            $rules = $this->validationRules();
        }

        return new Parser($this->validatorFactory()->make($this->all(), $rules)->validate());
    }
}
