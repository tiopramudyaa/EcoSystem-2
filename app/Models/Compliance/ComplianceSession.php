<?php

namespace App\Models\Compliance;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

/** Legal → Compliance: a training session of the curriculum (with meeting link, recording and material). */
class ComplianceSession extends Model
{
    use Auditable;

    protected static ?string $auditModule = 'Compliance';

    protected $fillable = [
        'title', 'description', 'session_date', 'start_time', 'end_time', 'duration_min', 'session_type', 'status',
        'facilitator', 'location', 'notes', 'meeting_url', 'recording_url', 'material_url',
    ];

    protected $casts = ['session_date' => 'date', 'duration_min' => 'integer'];

    /** What the details dialog of My Compliance → Training shows. */
    public function modalData(): array
    {
        return [
            'title' => $this->title, 'description' => $this->description, 'date' => $this->session_date->format('l, d F Y'),
            'time' => $this->start_time ? substr($this->start_time, 0, 5) . ($this->end_time ? '–' . substr($this->end_time, 0, 5) : '') : '', 'duration' => $this->duration_min,
            'status' => ComplianceOption::label('session_status', $this->status), 'facilitator' => $this->facilitator, 'location' => $this->location, 'notes' => $this->notes,
            'meeting_url' => $this->meeting_url, 'recording_url' => $this->recording_url, 'material_url' => $this->material_url,
        ];
    }
}
