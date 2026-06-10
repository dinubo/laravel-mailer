<?php

namespace Dinubo\Mailer\Http\Requests;

use Carbon\CarbonInterval;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Dinubo\Mailer\Mailer;
use Illuminate\Support\Str;

class NewsletterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'segment' => 'nullable|in:' . Mailer::getSegments()->pluck('value')->implode(','),
            'event' => 'nullable|in:' . Mailer::getEvents()->pluck('value')->implode(','),
            'action' => 'nullable|in:' . Mailer::getActions()->pluck('value')->implode(','),
            'category' => 'required|string|max:32',
            'is_active' => 'required|boolean',
            'daily_rate' => 'nullable|integer|min:1|required_with:segment',
            'after_sec' => 'sometimes|integer|min:0',
            'after' => ['required', 'regex:/^((Immediately)|([0-9]+)|(([0-9]+[ ]?(y(ear(s)?)?)[ ]?)?([0-9]+[ ]?(mo(nth(s)?)?)[ ]?)?([0-9]+[ ]?(d(ay(s)?)?)[ ]?)?([0-9]+[ ]?(h(our(s)?)?)[ ]?)?([0-9]+[ ]?(m(in(ute(s)?)?)?)[ ]?)?([0-9]+[ ]?(s(ec(ond(s)?)?)?))?))$/i'],
            'subject' => 'required|string|max:120',
            'body' => 'required|string',
        ];
    }

    protected function passedValidation(): void
    {
        /** @var \Illuminate\Validation\Validator $validator */
        $validator = $this->validator;

        $data = $validator->getData();

        Arr::set($data, 'after_sec', $this->afterInSeconds());

        $validator->setData($data);

        // $validator->setValue('after_sec', $this->afterInSeconds());
    }

    public function afterInSeconds()
    {
        if ($this->after === 'Immediately') {
            return 0;
        }

        $after = Str::of($this->after)
            ->upper()
            ->replace(['SECONDS', 'SECOND', 'SEC'], 'seconds')
            ->replace(['MINUTES', 'MINUTE', 'MIN'], 'minutes')
            ->replace(['HOURS', 'HOUR'], 'hours')
            ->replace(['DAYS', 'DAY'], 'days')
            ->replace(['MONTHS', 'MONTH', 'MO'], 'months')
            ->replace(['YEARS', 'YEAR'], 'years')
            ->replace('S', 'seconds')
            ->replace('M', 'minutes')
            ->replace('H', 'hours')
            ->replace('D', 'days')
            ->replace('Y', 'years');

        if (!(string)$after) {
            return 0;
        }

        $now = now();

        return $now->copy()->diffInSeconds(
            $now->add((string)$after)
        );
    }
}
