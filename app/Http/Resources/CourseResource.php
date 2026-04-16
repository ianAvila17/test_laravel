<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CourseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'price' => $this->price,
            'level' => $this->level,
            'is_published' => $this->is_published,
            'instructor' => new InstructorResource($this->whenLoaded('instructor')),
            'lessons_count' => $this->whenCounted('lessons'),
            'average_rating' => isset($this->average_rating) ? (float) $this->average_rating : null,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
