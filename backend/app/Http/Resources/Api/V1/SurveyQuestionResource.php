<?php

namespace App\Http\Resources\Api\V1;

use App\Models\SurveyQuestion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin SurveyQuestion */
class SurveyQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'type' => $this->type->value,
            'sort_order' => $this->sort_order,
            'options' => $this->options,
        ];
    }
}
