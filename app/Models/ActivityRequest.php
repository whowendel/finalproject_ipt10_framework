<?php

declare(strict_types=1);

namespace App\Models;

/**
 * ActivityRequest Entity Model.
 */
class ActivityRequest
{
    public int $id;
    public int $user_id;
    public string $title;
    public string $description;
    public string $event_date;
    public string $venue;
    public string $status;
    public string $created_at;
    public string $updated_at;

    // Optional joined data
    public ?string $submitter_name = null;
    public ?string $submitter_email = null;

    public function __construct(array $attributes = [])
    {
        $this->id = (int)($attributes['id'] ?? 0);
        $this->user_id = (int)($attributes['user_id'] ?? 0);
        $this->title = (string)($attributes['title'] ?? '');
        $this->description = (string)($attributes['description'] ?? '');
        $this->event_date = (string)($attributes['event_date'] ?? '');
        $this->venue = (string)($attributes['venue'] ?? '');
        $this->status = (string)($attributes['status'] ?? 'draft');
        $this->created_at = (string)($attributes['created_at'] ?? '');
        $this->updated_at = (string)($attributes['updated_at'] ?? '');

        if (isset($attributes['submitter_name'])) {
            $this->submitter_name = (string)$attributes['submitter_name'];
        }
        if (isset($attributes['submitter_email'])) {
            $this->submitter_email = (string)$attributes['submitter_email'];
        }
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'description' => $this->description,
            'event_date' => $this->event_date,
            'venue' => $this->venue,
            'status' => $this->status,
            'submitter_name' => $this->submitter_name,
            'submitter_email' => $this->submitter_email,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
